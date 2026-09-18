<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$employer = db()->query("SELECT u.user_id,u.username,u.first_name,u.last_name,u.email
    FROM users u JOIN employer_profiles ep ON ep.user_id=u.user_id
    WHERE u.role='employer' AND u.account_status='active'
    ORDER BY EXISTS(SELECT 1 FROM reviews r WHERE r.reviewee_user_id=u.user_id AND r.review_status='visible') DESC,u.user_id LIMIT 1")->fetch();
if (!$employer) {
    echo "employer profile render smoke test: SKIP (no active employer fixture)\n";
    exit;
}

$_SESSION['user'] = [
    'id' => (int) $employer['user_id'],
    'username' => $employer['username'],
    'name' => trim($employer['first_name'] . ' ' . $employer['last_name']),
    'role' => 'employer',
    'email' => $employer['email'],
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$renderProfile = static function (string $filter): string {
    $_SERVER['REQUEST_URI'] = '/Flex/employer/profile.php?jobs=' . $filter;
    $_GET = ['jobs' => $filter];
    ob_start();
    require APP_ROOT . '/employer/profile.php';
    return (string) ob_get_clean();
};

$html = $renderProfile('all');

foreach (['โปรไฟล์และประวัติของฉัน','ประวัติประกาศงาน','ข้อมูลบัญชี','แพ็กเกจ','การจ้างงานสำเร็จล่าสุด','รีวิวที่ได้รับจากผู้ทำงาน'] as $expected) {
    if (!str_contains($html, $expected)) throw new RuntimeException('Employer profile page is missing: ' . $expected);
}
if (!str_contains($html, 'employer/profile.php')) throw new RuntimeException('Employer profile account-menu link is missing');
$reviewCountStatement = db()->prepare("SELECT COUNT(*) FROM reviews WHERE reviewee_user_id=? AND review_status='visible'");
$reviewCountStatement->execute([(int) $employer['user_id']]);
if ((int) $reviewCountStatement->fetchColumn() > 0
    && !str_contains($html, 'action="' . BASE_URL . '/report-review.php"')
    && !str_contains($html, 'ผู้ดูแลตรวจสอบรายงานแล้ว')
    && !str_contains($html, 'ส่งรายงานแล้ว')) {
    throw new RuntimeException('Employer review report control is missing');
}
foreach (['open', 'closed', 'invalid'] as $filter) {
    $filteredHtml = $renderProfile($filter);
    if (!str_contains($filteredHtml, 'ประวัติประกาศงาน')) throw new RuntimeException('Employer job filter failed: ' . $filter);
}

echo "employer profile render smoke test: PASS\n";
