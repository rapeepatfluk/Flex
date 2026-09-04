<?php
/**
 * PHPMailer SMTP wrapper for FLEXJOB
 * Configure FLEXJOB_SMTP_USER and FLEXJOB_SMTP_PASS as environment variables.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';
require_once __DIR__ . '/../lib/phpmailer/Exception.php';

function send_mail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $replyToEmail = null,
    string $replyToName = ''
): bool
{
    $pdo = db();
    if (!mail_is_configured()) {
        try {
            $pdo->prepare("INSERT INTO email_log (to_email,to_name,subject,status,error_msg) VALUES (?,?,?,'failed',?)")
                ->execute([$toEmail, $toName, $subject, 'SMTP credentials are not configured']);
        } catch (\Throwable) {}
        return false;
    }

    $result = deliver_mail($toEmail, $toName, $subject, $htmlBody, $replyToEmail, $replyToName);
    try {
        $pdo->prepare('INSERT INTO email_log (to_email,to_name,subject,status,error_msg,sent_at) VALUES (?,?,?,?,?,?)')
            ->execute([$toEmail, $toName, $subject, $result['sent'] ? 'sent' : 'failed', $result['error'], $result['sent'] ? date('Y-m-d H:i:s') : null]);
    } catch (\Throwable) {}

    return $result['sent'];
}

/**
 * Add a notification email to the outbox. The queue worker sends it later so
 * the web request never waits for an SMTP connection or timeout.
 */
function queue_mail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $replyToEmail = null,
    string $replyToName = ''
): bool {
    try {
        $pdo = db();
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $pdo->prepare("INSERT INTO email_log (to_email,to_name,subject,status,error_msg) VALUES (?,?,?,'failed',?)")
                ->execute([$toEmail, $toName, $subject, 'Recipient email is invalid']);
            return false;
        }
        if (!mail_is_configured()) {
            $pdo->prepare("INSERT INTO email_log (to_email,to_name,subject,status,error_msg) VALUES (?,?,?,'failed',?)")
                ->execute([$toEmail, $toName, $subject, 'SMTP credentials are not configured']);
            return false;
        }

        $pdo->prepare("INSERT INTO email_log (to_email,to_name,subject,html_body,reply_to_email,reply_to_name,status,available_at) VALUES (?,?,?,?,?,?,'queued',NOW())")
            ->execute([$toEmail, $toName, $subject, $htmlBody, $replyToEmail, $replyToName]);
        return true;
    } catch (\Throwable) {
        return false;
    }
}

/** @return array{sent: bool, error: string|null} */
function deliver_mail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $replyToEmail = null,
    string $replyToName = ''
): array {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = email_layout($subject, $htmlBody);
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return ['sent' => true, 'error' => null];
    } catch (MailerException $e) {
        return ['sent' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    } catch (\Throwable $e) {
        return ['sent' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Claim and send a bounded batch of queued emails. This function is intended
 * for the CLI queue worker and uses row locks so concurrent runs do not send a
 * message twice.
 *
 * @return array{sent: int, retried: int, failed: int}
 */
function process_email_queue(PDO $pdo, int $limit = 20): array
{
    $limit = max(1, min(100, $limit));
    $result = ['sent' => 0, 'retried' => 0, 'failed' => 0];

    $pdo->beginTransaction();
    try {
        $pdo->exec("UPDATE email_log SET status='queued', locked_at=NULL WHERE status='processing' AND locked_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $statement = $pdo->query("SELECT id,to_email,to_name,subject,html_body,reply_to_email,reply_to_name,attempts FROM email_log WHERE status='queued' AND available_at <= NOW() ORDER BY id ASC LIMIT {$limit} FOR UPDATE");
        $messages = $statement->fetchAll();
        if ($messages) {
            $claim = $pdo->prepare("UPDATE email_log SET status='processing', attempts=attempts+1, locked_at=NOW() WHERE id=?");
            foreach ($messages as &$message) {
                $claim->execute([$message['id']]);
                $message['attempts']++;
            }
            unset($message);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    foreach ($messages ?? [] as $message) {
        $delivery = deliver_mail(
            $message['to_email'],
            (string) $message['to_name'],
            $message['subject'],
            (string) $message['html_body'],
            $message['reply_to_email'] ?: null,
            (string) $message['reply_to_name']
        );

        if ($delivery['sent']) {
            $pdo->prepare("UPDATE email_log SET status='sent', error_msg=NULL, locked_at=NULL, sent_at=NOW() WHERE id=? AND status='processing'")
                ->execute([$message['id']]);
            $result['sent']++;
            continue;
        }

        $willRetry = (int) $message['attempts'] < 3;
        $pdo->prepare($willRetry
            ? "UPDATE email_log SET status='queued', error_msg=?, locked_at=NULL, available_at=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=? AND status='processing'"
            : "UPDATE email_log SET status='failed', error_msg=?, locked_at=NULL WHERE id=? AND status='processing'")
            ->execute([$delivery['error'], $message['id']]);
        $result[$willRetry ? 'retried' : 'failed']++;
    }

    return $result;
}

function mail_is_configured(): bool
{
    return SMTP_USER !== '' && SMTP_PASS !== '';
}

/**
 * Wraps HTML body in a branded email layout (Blue & White Theme)
 */
function email_layout(string $title, string $body): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$safeTitle}</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Noto Sans Thai',Helvetica,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 20px;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
        <!-- Header (Blue Theme) -->
        <tr>
          <td style="background:#0052cc;padding:28px 36px;">
            <span style="font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-1px;">
              <span style="display:inline-block;background:#00a3ff;color:#ffffff;border-radius:6px;padding:2px 7px;margin-right:4px;font-size:17px;font-weight:bold;">F</span>
              FLEXJOB
            </span>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:36px 36px 28px;">
            {$body}
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f8fafc;padding:20px 36px;border-top:1px solid #e2e8f0;">
            <p style="margin:0;font-size:12px;color:#64748b;text-align:center;">
              อีเมลนี้ส่งโดยอัตโนมัติจากระบบ FLEXJOB &bull; กรุณาอย่าตอบกลับ
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Reusable blue CTA button for emails
 */
function email_btn(string $url, string $label): string
{
    return <<<HTML
<div style="text-align:center;margin:28px 0;">
  <a href="{$url}" style="display:inline-block;padding:14px 32px;background:#0052cc;color:#ffffff;text-decoration:none;border-radius:10px;font-size:15px;font-weight:600;letter-spacing:-0.3px;">{$label}</a>
</div>
HTML;
}
