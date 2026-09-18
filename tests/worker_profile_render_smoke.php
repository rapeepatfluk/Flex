<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$worker = db()->query("SELECT u.user_id,u.username,u.first_name,u.last_name,u.email
    FROM users u LEFT JOIN worker_profiles wp ON wp.user_id=u.user_id
    WHERE u.role='worker' AND u.account_status='active'
      AND NOT (wp.matching_survey_required_at IS NOT NULL AND wp.matching_survey_completed_at IS NULL)
    ORDER BY u.user_id LIMIT 1")->fetch();
if (!$worker) {
    echo "worker profile render smoke test: SKIP (no active worker fixture)\n";
    exit;
}

$_SESSION['user'] = [
    'id' => (int) $worker['user_id'],
    'username' => $worker['username'],
    'name' => trim($worker['first_name'] . ' ' . $worker['last_name']),
    'role' => 'worker',
    'email' => $worker['email'],
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/Flex/worker/profile.php';
$_GET = [];

ob_start();
require APP_ROOT . '/worker/profile.php';
$html = (string) ob_get_clean();

foreach (['โปรไฟล์และประวัติของฉัน', 'ประวัติการสมัครงาน', 'งานที่เสร็จสิ้น', 'งานที่ไม่ผ่าน', 'คำเชิญล่าสุด', 'รีวิวที่ฉันได้รับ'] as $expected) {
    if (!str_contains($html, $expected)) throw new RuntimeException('Worker profile page is missing: ' . $expected);
}
if (!str_contains($html, 'worker/profile.php')) throw new RuntimeException('Worker profile account-menu link is missing');
$reviewCountStatement = db()->prepare("SELECT COUNT(*) FROM reviews WHERE reviewee_user_id=? AND review_status='visible'");
$reviewCountStatement->execute([(int) $worker['user_id']]);
if ((int) $reviewCountStatement->fetchColumn() > 0
    && !str_contains($html, 'action="' . BASE_URL . '/report-review.php"')
    && !str_contains($html, 'ผู้ดูแลตรวจสอบรายงานแล้ว')
    && !str_contains($html, 'ส่งรายงานแล้ว')) {
    throw new RuntimeException('Worker review report control is missing');
}

echo "worker profile render smoke test: PASS\n";
