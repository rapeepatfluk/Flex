<?php
require_once __DIR__ . '/config/config.php';
require_login();

$notificationId = (int) ($_GET['id'] ?? 0);
$statement = db()->prepare('SELECT notification_url FROM notifications WHERE notification_id=? AND user_id=?');
$statement->execute([$notificationId, user()['id']]);
$notification = $statement->fetch();

if ($notification) {
    db()->prepare('UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?')->execute([$notificationId, user()['id']]);
    redirect($notification['notification_url'] ?: dashboard_path(user()['role']));
}

redirect(dashboard_path(user()['role']));
