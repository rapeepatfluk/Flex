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
    $statement = $pdo->prepare("SELECT a.application_id,a.job_id,j.job_title,j.employer_user_id FROM applications a JOIN jobs j ON j.job_id=a.job_id WHERE a.application_id=? AND a.worker_user_id=? AND a.application_status='submitted' FOR UPDATE");
    $statement->execute([$applicationId, user()['id']]);
    $application = $statement->fetch();
    if (!$application) throw new RuntimeException('ยกเลิกได้เฉพาะใบสมัครที่กำลังรอพิจารณา');

    $pdo->prepare("UPDATE applications SET application_status='withdrawn',withdrawn_at=NOW() WHERE application_id=? AND worker_user_id=? AND application_status='submitted'")
        ->execute([$applicationId, user()['id']]);
    $pdo->prepare('INSERT INTO notifications (user_id,notification_title,notification_message,notification_url) VALUES (?,?,?,?)')
        ->execute([(int) $application['employer_user_id'], 'ผู้สมัครถอนใบสมัคร', user()['name'] . ' ถอนใบสมัครงาน: ' . $application['job_title'], 'employer/applicants.php?job=' . $application['job_id']]);
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
