<?php
require_once __DIR__ . '/config/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$pdo = db();
$userId = (int) user()['id'];
$response = ['unread' => notification_unread_count($pdo, $userId)];
if (($_GET['details'] ?? '') === '1') {
    $response['notifications'] = array_map(static fn(array $notification): array => [
        'id' => (int) $notification['notification_id'],
        'title' => $notification['notification_title'],
        'message' => $notification['notification_message'],
        'url' => BASE_URL . '/notification.php?id=' . (int) $notification['notification_id'],
        'is_read' => (bool) $notification['is_read'],
        'created_at' => date('d/m/Y H:i', strtotime($notification['created_at'])),
    ], notification_latest($pdo, $userId));
}
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
