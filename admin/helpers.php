<?php

function admin_notify_user(PDO $pdo, int $userId, string $title, string $message): void
{
    $roleStmt = $pdo->prepare('SELECT role FROM users WHERE user_id=?');
    $roleStmt->execute([$userId]);
    $path = $roleStmt->fetchColumn() === 'worker' ? 'worker/dashboard.php' : 'employer/dashboard.php';

    notification_create($pdo, $userId, $title, $message, $path);
}
