<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (mail_is_configured()) {
    echo "mailer configuration smoke test: SKIP (SMTP is configured)\n";
    exit;
}

$pdo = db();
$pdo->beginTransaction();
try {
    $sent = send_mail('smtp-test@flexjob.local', 'SMTP Test', 'SMTP configuration test', 'test');
    $statement = $pdo->prepare("SELECT status,error_msg FROM email_log WHERE to_email=? ORDER BY id DESC LIMIT 1");
    $statement->execute(['smtp-test@flexjob.local']);
    $log = $statement->fetch();
    if ($sent || !$log || $log['status'] !== 'failed' || $log['error_msg'] !== 'SMTP credentials are not configured') {
        throw new RuntimeException('Missing SMTP configuration was not reported correctly');
    }
    echo "mailer configuration smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
