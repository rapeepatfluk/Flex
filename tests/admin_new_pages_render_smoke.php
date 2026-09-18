<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$admin = db()->query("SELECT user_id,username,first_name,last_name,email FROM users WHERE role='admin' AND account_status='active' ORDER BY user_id LIMIT 1")->fetch();
if (!$admin) {
    echo "admin new pages render smoke test: SKIP (no active admin fixture)\n";
    exit;
}

$_SESSION['user'] = [
    'id' => (int) $admin['user_id'],
    'username' => $admin['username'],
    'name' => trim($admin['first_name'] . ' ' . $admin['last_name']),
    'role' => 'admin',
    'email' => $admin['email'],
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = [];

$pdo = db();
$pdo->beginTransaction();
try {
    $_SERVER['REQUEST_URI'] = '/Flex/admin/subscriptions.php';
    $_GET = [];
    ob_start();
    require APP_ROOT . '/admin/subscriptions.php';
    $subscriptionsHtml = (string) ob_get_clean();
    if (!str_contains($subscriptionsHtml, 'ตรวจสลิปแพ็กเกจ Pro')) throw new RuntimeException('Admin subscription page did not render');

    $_SERVER['REQUEST_URI'] = '/Flex/admin/reviews.php';
    $_GET = ['status' => 'all'];
    ob_start();
    require APP_ROOT . '/admin/reviews.php';
    $reviewsHtml = (string) ob_get_clean();
    if (!str_contains($reviewsHtml, 'ดูแลรีวิว')) throw new RuntimeException('Admin review moderation page did not render');

    $_SERVER['REQUEST_URI'] = '/Flex/admin/reports.php';
    $_GET = [];
    ob_start();
    require APP_ROOT . '/admin/reports.php';
    $reportsHtml = (string) ob_get_clean();
    if (!str_contains($reportsHtml, 'รายงานระบบ FLEXJOB')) throw new RuntimeException('Admin reports page did not render');

    $_SERVER['REQUEST_URI'] = '/Flex/admin/reports.php?print=1';
    $_GET = ['print' => '1'];
    ob_start();
    require APP_ROOT . '/admin/reports.php';
    $printHtml = (string) ob_get_clean();
    if (!str_contains($printHtml, 'พิมพ์ / บันทึกเป็น PDF')) throw new RuntimeException('Admin report print page did not render');
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

echo "admin new pages render smoke test: PASS\n";
