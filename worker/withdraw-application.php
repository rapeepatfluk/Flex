<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('worker/dashboard.php');

try {
    verify_csrf();
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    if ($applicationId < 1) throw new RuntimeException('ไม่พบใบสมัครที่ต้องการยกเลิก');

    $pdo = db();
    $pdo->beginTransaction();
    $jobIdStatement = $pdo->prepare('SELECT job_id FROM applications WHERE application_id=? AND worker_user_id=?');
    $jobIdStatement->execute([$applicationId, user()['id']]);
    $jobId = (int) $jobIdStatement->fetchColumn();
    if (!$jobId) throw new RuntimeException('ไม่พบใบสมัครที่ต้องการยกเลิก');

    $jobStatement = $pdo->prepare('SELECT job_title,employer_user_id FROM jobs WHERE job_id=? FOR UPDATE');
    $jobStatement->execute([$jobId]);
    $job = $jobStatement->fetch();
    if (!$job) throw new RuntimeException('ไม่พบประกาศงานของใบสมัครนี้');

    $statement = $pdo->prepare("SELECT application_id,job_id FROM applications WHERE application_id=? AND worker_user_id=? AND application_status IN ('submitted','eligible','interview_passed') FOR UPDATE");
    $statement->execute([$applicationId, user()['id']]);
    $application = $statement->fetch();
    if (!$application) throw new RuntimeException('ไม่สามารถถอนใบสมัครที่สิ้นสุดกระบวนการแล้ว');

    $pdo->prepare("UPDATE applications SET application_status='withdrawn',withdrawn_at=NOW() WHERE application_id=? AND worker_user_id=? AND application_status IN ('submitted','eligible','interview_passed')")
        ->execute([$applicationId, user()['id']]);
    notification_create($pdo, (int) $job['employer_user_id'], 'ผู้สมัครถอนใบสมัคร', user()['name'] . ' ถอนใบสมัครงาน: ' . $job['job_title'], 'employer/applicants.php?job=' . $application['job_id']);
    $pdo->commit();
    flash('success', 'ถอนใบสมัครเรียบร้อยแล้ว');
} catch (RuntimeException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    flash('error', $e->getMessage());
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Withdraw application failed: ' . $e->getMessage());
    flash('error', 'ไม่สามารถถอนใบสมัครได้ กรุณาลองใหม่อีกครั้ง');
}

redirect('worker/application-detail.php?id=' . (int) ($_POST['application_id'] ?? 0));
