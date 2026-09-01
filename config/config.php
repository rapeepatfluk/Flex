<?php

declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'db_flexjob';
const DB_USER = 'root';
const DB_PASS = '';
const BASE_URL = '/Flex';
const APP_ROOT = __DIR__ . '/..';
const FLEXJOB_PROVINCE = 'บุรีรัมย์';

// ── Gmail SMTP ──────────────────────────────────────────────────────────────
// 1. เปิด 2-Step Verification ที่ myaccount.google.com
// 2. ไปที่ Security → App passwords → สร้าง password สำหรับ "Mail"
// 3. ใส่ค่าด้านล่าง
$smtpLocalConfig = [];
$smtpLocalPath = __DIR__ . '/smtp.local.php';
if (is_file($smtpLocalPath)) {
    $loadedSmtpConfig = require $smtpLocalPath;
    if (is_array($loadedSmtpConfig)) $smtpLocalConfig = $loadedSmtpConfig;
}
const SMTP_HOST      = 'smtp.gmail.com';
const SMTP_PORT      = 587;
define('SMTP_USER', getenv('FLEXJOB_SMTP_USER') ?: ($smtpLocalConfig['username'] ?? ''));
define('SMTP_PASS', getenv('FLEXJOB_SMTP_PASS') ?: ($smtpLocalConfig['app_password'] ?? ''));
const SMTP_FROM_NAME = 'FLEXJOB';
unset($smtpLocalConfig, $smtpLocalPath, $loadedSmtpConfig);

$paymentLocalConfig = [];
$paymentLocalPath = __DIR__ . '/payment.local.php';
if (is_file($paymentLocalPath)) {
    $loadedPaymentConfig = require $paymentLocalPath;
    if (is_array($loadedPaymentConfig)) $paymentLocalConfig = $loadedPaymentConfig;
}
define('PROMPTPAY_ID', getenv('FLEXJOB_PROMPTPAY_ID') ?: ($paymentLocalConfig['promptpay_id'] ?? ''));
define('PROMPTPAY_RECIPIENT_NAME', getenv('FLEXJOB_PROMPTPAY_RECIPIENT_NAME') ?: ($paymentLocalConfig['recipient_name'] ?? ''));
unset($paymentLocalConfig, $paymentLocalPath, $loadedPaymentConfig);

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
function user(): ?array
{
    return $_SESSION['user'] ?? null;
}
function is_role(string $role): bool
{
    return user() && user()['role'] === $role;
}
function redirect(string $path): never
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}
function require_login(?string $role = null): void
{
    if (!user() || ($role && !is_role($role))) redirect('auth/login.php');

    $statusStmt = db()->prepare('SELECT account_status FROM users WHERE user_id=?');
    $statusStmt->execute([user()['id']]);
    if ($statusStmt->fetchColumn() !== 'active') {
        $_SESSION = [];
        session_destroy();
        redirect('auth/login.php');
    }
}
function dashboard_path(string $role): string
{
    return ['worker' => 'worker/index.php', 'employer' => 'employer/index.php', 'admin' => 'admin/dashboard.php'][$role] ?? 'index.php';
}
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) throw new RuntimeException('คำขอหมดอายุ กรุณาลองใหม่อีกครั้ง');
}
function job_type(string $type): string
{
    return ['part_time' => 'พาร์ทไทม์', 'event' => 'งานอีเวนต์', 'freelance' => 'ฟรีแลนซ์'][$type] ?? $type;
}
function pay_text(array $job): string
{
    return number_format((float)$job['pay_amount']) . ' บาท/' . ['hour' => 'ชม.', 'day' => 'วัน', 'project' => 'โปรเจกต์'][$job['pay_unit']];
}
function upload_file(string $field, array $allowed, string $folder): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
    if ((int) $_FILES[$field]['size'] > 8 * 1024 * 1024) throw new RuntimeException('ไฟล์ต้องมีขนาดไม่เกิน 8 MB');
    if (!is_uploaded_file($_FILES[$field]['tmp_name'])) throw new RuntimeException('ไฟล์อัปโหลดไม่ถูกต้อง');
    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) throw new RuntimeException('ชนิดไฟล์ไม่ถูกต้อง');
    $mimeMap = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    if (!isset($mimeMap[$extension]) || !in_array($mime, $mimeMap[$extension], true)) throw new RuntimeException('เนื้อหาไฟล์ไม่ตรงกับชนิดไฟล์');
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && @getimagesize($_FILES[$field]['tmp_name']) === false) throw new RuntimeException('ไฟล์รูปภาพไม่ถูกต้อง');
    $directory = APP_ROOT . '/uploads/' . $folder;
    if (!is_dir($directory)) mkdir($directory, 0775, true);
    $filename = bin2hex(random_bytes(10)) . '.' . $extension;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('ไม่สามารถบันทึกไฟล์ได้');
    return 'uploads/' . $folder . '/' . $filename;
}

// Auto-load email helpers (fail-safe — won't break if SMTP not configured)
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/notify.php';
require_once APP_ROOT . '/services/ApplicationService.php';
require_once APP_ROOT . '/services/MatchingService.php';
require_once APP_ROOT . '/services/PromotionService.php';
