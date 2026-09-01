<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$userId = (int) $pdo->query("SELECT user_id FROM users WHERE account_status='active' ORDER BY user_id LIMIT 1")->fetchColumn();
if (!$userId) {
    echo "notification smoke test: SKIP (no active user fixture)\n";
    exit;
}

$pdo->beginTransaction();
try {
    $before = notification_unread_count($pdo, $userId);
    for ($i = 1; $i <= 10; $i++) {
        $id = notification_create($pdo, $userId, 'ทดสอบแจ้งเตือน ' . $i, 'ข้อความทดสอบ ' . $i, 'index.php');
        if ($id < 1) throw new RuntimeException('Notification id was not returned');
    }
    if (notification_unread_count($pdo, $userId) !== $before + 10) {
        throw new RuntimeException('Unread count does not include every notification');
    }
    $latest = notification_latest($pdo, $userId, 8);
    if (count($latest) !== 8 || $latest[0]['notification_title'] !== 'ทดสอบแจ้งเตือน 10') {
        throw new RuntimeException('Latest notification feed is incorrect');
    }
    echo "notification smoke test: PASS\n";
} finally {
    $pdo->rollBack();
}
