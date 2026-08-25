<?php

function admin_notify_user(PDO $pdo, int $userId, string $title, string $message): void
{
    $roleStmt = $pdo->prepare('SELECT role FROM users WHERE user_id=?');
    $roleStmt->execute([$userId]);
    $path = $roleStmt->fetchColumn() === 'worker' ? 'worker/dashboard.php' : 'employer/dashboard.php';

    $pdo->prepare('INSERT INTO notifications (user_id, notification_title, notification_message, notification_url) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $title, $message, $path]);
}
