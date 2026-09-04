<?php
require_once __DIR__ . '/config/config.php';
require_login();

$pdo = db();
$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$path = null;

if ($type === 'profile_resume' || $type === 'profile_portfolio') {
    $column = $type === 'profile_resume' ? 'resume_file_path' : 'portfolio_file_path';
    $statement = $pdo->prepare("SELECT user_id,{$column} file_path FROM worker_profiles WHERE user_id=?");
    $statement->execute([$id]);
    $file = $statement->fetch();
    if ($file && (is_role('admin') || (is_role('worker') && (int) user()['id'] === (int) $file['user_id']))) $path = $file['file_path'];
} elseif ($type === 'application_resume' || $type === 'application_portfolio') {
    $statement = $pdo->prepare("SELECT a.worker_user_id,j.employer_user_id,COALESCE(a.resume_file_path,wp.resume_file_path) resume_path,wp.portfolio_file_path portfolio_path FROM applications a JOIN jobs j ON j.job_id=a.job_id LEFT JOIN worker_profiles wp ON wp.user_id=a.worker_user_id WHERE a.application_id=?");
    $statement->execute([$id]);
    $file = $statement->fetch();
    $allowed = $file && (is_role('admin') || (is_role('worker') && (int) user()['id'] === (int) $file['worker_user_id']) || (is_role('employer') && (int) user()['id'] === (int) $file['employer_user_id']));
    if ($allowed) $path = $type === 'application_resume' ? $file['resume_path'] : $file['portfolio_path'];
} elseif ($type === 'candidate_resume' || $type === 'candidate_portfolio') {
    $workerId = (int) ($_GET['worker'] ?? 0);
    $jobId = (int) ($_GET['job'] ?? 0);
    $column = $type === 'candidate_resume' ? 'resume_file_path' : 'portfolio_file_path';
    $statement = $pdo->prepare("SELECT wp.{$column} AS file_path
        FROM worker_profiles wp
        JOIN users w ON w.user_id=wp.user_id
        JOIN jobs j ON j.job_id=?
        WHERE wp.user_id=? AND w.role='worker' AND w.account_status='active'
          AND wp.profile_visibility='searchable' AND wp.work_province=?
          AND j.employer_user_id=? AND j.work_province=? AND j.job_status='published'
          AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())");
    $statement->execute([$jobId, $workerId, FLEXJOB_PROVINCE, (int) user()['id'], FLEXJOB_PROVINCE]);
    $file = $statement->fetch();
    if ($file && is_role('employer') && matching_employer_is_verified($pdo, (int) user()['id'])) $path = $file['file_path'];
} elseif ($type === 'employer_document') {
    $statement = $pdo->prepare('SELECT employer_user_id,document_file_path file_path FROM employer_documents WHERE employer_document_id=?');
    $statement->execute([$id]);
    $file = $statement->fetch();
    if ($file && (is_role('admin') || (is_role('employer') && (int) user()['id'] === (int) $file['employer_user_id']))) $path = $file['file_path'];
} elseif ($type === 'promotion_slip') {
    $statement = $pdo->prepare('SELECT employer_user_id,payment_slip_path file_path FROM job_promotions WHERE promotion_id=?');
    $statement->execute([$id]);
    $file = $statement->fetch();
    if ($file && (is_role('admin') || (is_role('employer') && (int) user()['id'] === (int) $file['employer_user_id']))) $path = $file['file_path'];
}

$uploadsRoot = realpath(APP_ROOT . '/uploads');
$absolutePath = $path ? realpath(APP_ROOT . '/' . ltrim((string) $path, '/\\')) : false;
if (!$uploadsRoot || !$absolutePath || !str_starts_with(strtolower($absolutePath), strtolower($uploadsRoot . DIRECTORY_SEPARATOR)) || !is_file($absolutePath)) {
    http_response_code(404);
    exit('ไม่พบไฟล์หรือคุณไม่มีสิทธิ์เข้าถึง');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($absolutePath) ?: 'application/octet-stream';
$extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
$disposition = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true) ? 'inline' : 'attachment';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($absolutePath));
header('Content-Disposition: ' . $disposition . '; filename="flexjob-document.' . $extension . '"');
header('X-Content-Type-Options: nosniff');
readfile($absolutePath);
exit;
