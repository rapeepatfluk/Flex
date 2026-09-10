<?php
require_once __DIR__ . '/config/config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$returnTo = (string) ($_POST['return_to'] ?? (BASE_URL . '/index.php'));
$returnPath = parse_url($returnTo, PHP_URL_PATH) ?: (BASE_URL . '/index.php');
$returnQuery = parse_url($returnTo, PHP_URL_QUERY);
if (!str_starts_with($returnPath, BASE_URL . '/')) $returnPath = BASE_URL . '/index.php';
$relative = ltrim(substr($returnPath, strlen(BASE_URL)), '/');
if ($returnQuery) $relative .= '?' . $returnQuery;

try {
    verify_csrf();
    review_report(db(), (int) ($_POST['review_id'] ?? 0), (int) user()['id'], (string) ($_POST['report_reason'] ?? ''));
    foreach (db()->query("SELECT user_id FROM users WHERE role='admin' AND account_status='active'")->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
        notification_create(db(), (int) $adminId, 'มีรายงานรีวิวใหม่', 'มีผู้ใช้รายงานความคิดเห็น โปรดตรวจสอบเนื้อหา', 'admin/reviews.php');
    }
    flash('success', 'ส่งรายงานให้ผู้ดูแลตรวจสอบแล้ว');
} catch (Throwable $exception) {
    flash('error', $exception->getMessage());
}
redirect($relative ?: 'index.php');
