<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('employer/dashboard.php');

$pdo = db();
$employerId = (int) user()['id'];
$jobId = (int) ($_POST['job_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
try {
    verify_csrf();
    if (!$jobId || !in_array($action,['hide','publish'],true)) throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    $pdo->beginTransaction();
    $pdo->prepare('SELECT user_id FROM users WHERE user_id=? FOR UPDATE')->execute([$employerId]);
    $jobStatement = $pdo->prepare('SELECT job_status,application_deadline,open_positions FROM jobs WHERE job_id=? AND employer_user_id=? FOR UPDATE');
    $jobStatement->execute([$jobId,$employerId]);
    $job = $jobStatement->fetch();
    if (!$job) throw new RuntimeException('ไม่พบประกาศงาน');
    if ($action === 'publish') {
        if (!matching_employer_is_verified($pdo, $employerId)) throw new RuntimeException('บัญชีผู้ว่าจ้างต้องผ่านการยืนยันก่อนเปิดประกาศ');
        if ($job['application_deadline'] && $job['application_deadline'] < date('Y-m-d')) throw new RuntimeException('กรุณาแก้ไขวันปิดรับสมัครก่อนเปิดประกาศ');
        $completedStatement = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id=? AND application_status='completed'");
        $completedStatement->execute([$jobId]);
        if ((int) $completedStatement->fetchColumn() >= (int) $job['open_positions']) throw new RuntimeException('กรุณาเพิ่มจำนวนคนที่เปิดรับก่อนเปิดประกาศอีกครั้ง');
        subscription_assert_can_publish_job($pdo,$employerId,$jobId);
        $status='published';
    } else {
        $status='hidden';
    }
    $pdo->prepare('UPDATE jobs SET job_status=? WHERE job_id=? AND employer_user_id=?')->execute([$status,$jobId,$employerId]);
    $pdo->commit();
    flash('success',$status==='published'?'เปิดประกาศแล้ว':'ซ่อนประกาศแล้ว');
} catch(Throwable $exception) {
    if($pdo->inTransaction()) $pdo->rollBack();
    flash('error',$exception->getMessage());
}
redirect('employer/dashboard.php#all-jobs');
