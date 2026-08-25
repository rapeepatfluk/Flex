<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$role = $argv[1] ?? '';
if (!in_array($role, ['worker', 'employer'], true)) {
    fwrite(STDERR, "Usage: php tests/header_avatar_smoke.php worker|employer\n");
    exit(2);
}

$sql = $role === 'worker'
    ? "SELECT u.user_id,CONCAT(u.first_name,' ',u.last_name) name,wp.profile_image_path avatar FROM users u JOIN worker_profiles wp ON wp.user_id=u.user_id WHERE wp.profile_image_path IS NOT NULL AND wp.profile_image_path<>'' LIMIT 1"
    : "SELECT u.user_id,CONCAT(u.first_name,' ',u.last_name) name,ep.company_logo_path avatar FROM users u JOIN employer_profiles ep ON ep.user_id=u.user_id WHERE ep.company_logo_path IS NOT NULL AND ep.company_logo_path<>'' LIMIT 1";
$account = db()->query($sql)->fetch();
if (!$account) {
    fwrite(STDOUT, "header avatar smoke test ({$role}): SKIP (no image fixture)\n");
    exit(0);
}

$_SESSION['user'] = ['id' => (int) $account['user_id'], 'role' => $role, 'name' => $account['name']];
$_SERVER['REQUEST_URI'] = '/Flex/';
ob_start();
require APP_ROOT . '/partials/header.php';
$html = (string) ob_get_clean();

$expected = BASE_URL . '/' . $account['avatar'];
if (!str_contains($html, 'class="account-avatar"') || !str_contains($html, 'src="' . e($expected) . '"')) {
    fwrite(STDERR, "header avatar smoke test ({$role}): FAIL\n");
    exit(1);
}

fwrite(STDOUT, "header avatar smoke test ({$role}): PASS\n");
