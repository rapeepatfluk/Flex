<?php

declare(strict_types=1);

function notification_create(PDO $pdo, int $userId, string $title, string $message, ?string $url = null): int
{
    $title = trim($title);
    $message = trim($message);
    $url = $url !== null ? ltrim(trim($url), '/') : null;
    if ($userId < 1 || $title === '' || $message === '') throw new RuntimeException('ข้อมูลการแจ้งเตือนไม่ครบถ้วน');
    if (mb_strlen($title) > 180) $title = mb_substr($title, 0, 177) . '...';
    if ($url !== null && mb_strlen($url) > 255) throw new RuntimeException('ลิงก์การแจ้งเตือนยาวเกินไป');

    $statement = $pdo->prepare('INSERT INTO notifications (user_id,notification_title,notification_message,notification_url) VALUES (?,?,?,?)');
    $statement->execute([$userId, $title, $message, $url ?: null]);
    return (int) $pdo->lastInsertId();
}

function notification_create_for_role(PDO $pdo, string $role, string $title, string $message, ?string $url = null): int
{
    $users = $pdo->prepare("SELECT user_id FROM users WHERE role=? AND account_status='active'");
    $users->execute([$role]);
    $count = 0;
    foreach ($users->fetchAll(PDO::FETCH_COLUMN) as $userId) {
        notification_create($pdo, (int) $userId, $title, $message, $url);
        $count++;
    }
    return $count;
}

function notification_unread_count(PDO $pdo, int $userId): int
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $statement->execute([$userId]);
    return (int) $statement->fetchColumn();
}

function notification_latest(PDO $pdo, int $userId, int $limit = 8): array
{
    $limit = max(1, min(50, $limit));
    $statement = $pdo->prepare("SELECT notification_id,notification_title,notification_message,notification_url,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC,notification_id DESC LIMIT {$limit}");
    $statement->execute([$userId]);
    return $statement->fetchAll();
}
