<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$fixture = db()->query("SELECT a.application_id,a.worker_user_id,u.first_name,u.last_name,u.email
    FROM applications a
    JOIN users u ON u.user_id=a.worker_user_id
    WHERE a.application_status IN ('eligible','interview_passed','completed')
      AND u.account_status='active'
    ORDER BY a.application_id
    LIMIT 1")->fetch();

if (!$fixture) {
    echo "worker email link smoke test: SKIP (no contact-enabled application fixture)\n";
    exit;
}

$_SESSION['user'] = [
    'id' => (int) $fixture['worker_user_id'],
    'name' => trim($fixture['first_name'] . ' ' . $fixture['last_name']),
    'role' => 'worker',
    'email' => $fixture['email'],
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/Flex/worker/application-detail.php';
$_GET['id'] = (string) $fixture['application_id'];

ob_start();
require APP_ROOT . '/worker/application-detail.php';
$html = (string) ob_get_clean();

if (!preg_match('/href="https:\/\/mail\.google\.com\/mail\/\?view=cm&amp;fs=1&amp;to=[^\"]+&amp;su=[^\"]+&amp;body=[^\"]+"/', $html)) {
    throw new RuntimeException('Worker Gmail compose link is missing or incomplete');
}
if (!str_contains($html, 'target="_blank"') || !str_contains($html, 'rel="noopener noreferrer"')) {
    throw new RuntimeException('Worker Gmail compose link does not open safely in a new tab');
}
if (!str_contains($html, 'เปิด Gmail ในแท็บใหม่')) {
    throw new RuntimeException('Worker email link is missing its accessible label');
}

echo "worker email link smoke test: PASS\n";
