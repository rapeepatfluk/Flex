<?php
require_once __DIR__ . '/config/config.php';
require_login('worker');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('jobs.php');

try {
    verify_csrf();
} catch (RuntimeException $e) {
    flash('error', $e->getMessage());
    redirect('jobs.php');
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$check = db()->prepare("SELECT job_id,job_title,employer_user_id FROM jobs WHERE job_id=? AND work_province=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
$check->execute([$jobId, FLEXJOB_PROVINCE]);
$job = $check->fetch();
if (!$job) {
    flash('error', 'งานนี้ไม่เปิดรับสมัครแล้ว');
    redirect('jobs.php');
}

$pdo = db();
$profile = $pdo->prepare('SELECT resume_file_path FROM worker_profiles WHERE user_id=?');
$profile->execute([user()['id']]);

try {
    $pdo->beginTransaction();
    $existing = $pdo->prepare('SELECT application_id,application_status FROM applications WHERE job_id=? AND worker_user_id=? FOR UPDATE');
    $existing->execute([$jobId, user()['id']]);
    $application = $existing->fetch();

    if ($application && $application['application_status'] !== 'withdrawn') {
        throw new RuntimeException('คุณสมัครงานนี้ไปแล้ว');
    }

    $isReapplication = (bool) $application;
    if ($application) {
        $pdo->prepare("UPDATE applications SET application_status='submitted',withdrawn_at=NULL,cover_note=?,resume_file_path=?,created_at=CURRENT_TIMESTAMP WHERE application_id=?")
            ->execute([trim($_POST['cover_note'] ?? ''), $profile->fetchColumn(), $application['application_id']]);
        $appId = (int) $application['application_id'];
        $message = 'ส่งใบสมัครใหม่เรียบร้อยแล้ว ผู้ว่าจ้างจะติดต่อกลับผ่านระบบ';
    } else {
        $pdo->prepare('INSERT INTO applications (job_id,worker_user_id,cover_note,resume_file_path) VALUES (?,?,?,?)')
            ->execute([$jobId, user()['id'], trim($_POST['cover_note'] ?? ''), $profile->fetchColumn()]);
        $appId = (int) $pdo->lastInsertId();
        $message = 'ส่งใบสมัครเรียบร้อยแล้ว ผู้ว่าจ้างจะติดต่อกลับผ่านระบบ';
    }

    $invitationCheck = $pdo->prepare("SELECT job_invitation_id FROM job_invitations WHERE job_id=? AND worker_user_id=? AND invitation_status IN ('sent','viewed','accepted')");
    $invitationCheck->execute([$jobId, user()['id']]);
    $cameFromInvitation = (bool) $invitationCheck->fetchColumn();
    $invitationUpdate = $pdo->prepare("UPDATE job_invitations SET invitation_status='accepted',responded_at=COALESCE(responded_at,NOW()) WHERE job_id=? AND worker_user_id=? AND invitation_status IN ('sent','viewed')");
    $invitationUpdate->execute([$jobId, user()['id']]);

    $employerTitle = $isReapplication
        ? 'มีผู้สมัครส่งใบสมัครใหม่'
        : ($cameFromInvitation ? 'ผู้ได้รับเชิญส่งใบสมัครแล้ว' : 'มีผู้สมัครงานใหม่');
    notification_create(
        $pdo,
        (int) $job['employer_user_id'],
        $employerTitle,
        user()['name'] . ' สมัครงาน: ' . $job['job_title'],
        'employer/applicant-detail.php?id=' . $appId . '&job=' . $jobId
    );
    notification_create(
        $pdo,
        (int) user()['id'],
        'ส่งใบสมัครสำเร็จ',
        'ใบสมัครงาน “' . $job['job_title'] . '” ถูกส่งให้ผู้ว่าจ้างแล้ว',
        'worker/application-detail.php?id=' . $appId
    );
    $pdo->commit();

    notify_employer_new_applicant($appId);
    flash('success', $message);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Application failed: ' . $e->getMessage());
    flash('error', 'ไม่สามารถส่งใบสมัครได้ กรุณาลองใหม่อีกครั้ง');
}

redirect('worker/dashboard.php');
