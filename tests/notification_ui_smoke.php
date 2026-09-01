<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$root = dirname(__DIR__);
$header = file_get_contents($root . '/partials/header.php');
$script = file_get_contents($root . '/assets/js/notifications.js');
$apply = file_get_contents($root . '/apply.php');
$invitations = file_get_contents($root . '/worker/invitations.php');
$statusNotify = file_get_contents($root . '/config/notify.php');

foreach (['notification_unread_count', 'id="notificationMenu"', 'id="notificationCount"', 'id="notificationList"', '/notifications.php'] as $required) {
    if (!str_contains($header, $required)) throw new RuntimeException('Notification bell markup is incomplete: ' . $required);
}
foreach (['30000', 'document.hidden', 'visibilitychange', '?details=1', 'cache: \'no-store\''] as $required) {
    if (!str_contains($script, $required)) throw new RuntimeException('Notification polling behavior is incomplete: ' . $required);
}
foreach (['มีผู้สมัครงานใหม่', 'ส่งใบสมัครสำเร็จ', 'ผู้ได้รับเชิญส่งใบสมัครแล้ว'] as $required) {
    if (!str_contains($apply, $required)) throw new RuntimeException('Application notification is missing: ' . $required);
}
foreach (['ผู้หางานตอบรับคำเชิญ', 'ผู้หางานปฏิเสธคำเชิญ'] as $required) {
    if (!str_contains($invitations, $required)) throw new RuntimeException('Invitation response notification is missing: ' . $required);
}
if (!str_contains($statusNotify, 'อัปเดตสถานะใบสมัคร') || !str_contains($statusNotify, 'notification_create(')) {
    throw new RuntimeException('Application status bell notification is missing');
}

echo "notification UI smoke test: PASS\n";
