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

function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    if (!mail_is_configured()) {
        try {
            db()->prepare("INSERT INTO email_log (to_email,subject,status,error_msg) VALUES (?,?,'failed',?)")
                ->execute([$toEmail, $subject, 'SMTP credentials are not configured']);
        } catch (\Throwable) {}
        return false;
    }
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
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = email_layout($subject, $htmlBody);
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();

        // Log success
        db()->prepare("INSERT INTO email_log (to_email, subject, status) VALUES (?,?,'sent')")
             ->execute([$toEmail, $subject]);
        return true;

    } catch (MailerException $e) {
        // Log failure
        try {
            db()->prepare("INSERT INTO email_log (to_email, subject, status, error_msg) VALUES (?,?,'failed',?)")
                 ->execute([$toEmail, $subject, $mail->ErrorInfo]);
        } catch (\Throwable) {}
        return false;
    }
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
    return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
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
