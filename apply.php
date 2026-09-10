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
$pdo = db();
$profile = $pdo->prepare('SELECT resume_file_path FROM worker_profiles WHERE user_id=?');
$profile->execute([user()['id']]);
$resumeFilePath = $profile->fetchColumn();

try {
    $pdo->beginTransaction();
    $ownerStatement = $pdo->prepare('SELECT employer_user_id FROM jobs WHERE job_id=?');
    $ownerStatement->execute([$jobId]);
    $employerId = (int) $ownerStatement->fetchColumn();
    if (!$employerId) throw new RuntimeException('งานนี้ไม่เปิดรับสมัครแล้ว');

    $accountStatement = $pdo->prepare('SELECT account_status FROM users WHERE user_id=? FOR UPDATE');
    $accountStatement->execute([$employerId]);
    $employerAccountStatus = $accountStatement->fetchColumn();
    $check = $pdo->prepare('SELECT job_id,job_title,employer_user_id,job_status,application_deadline,open_positions
        FROM jobs WHERE job_id=? AND employer_user_id=? FOR UPDATE');
    $check->execute([$jobId, $employerId]);
    $job = $check->fetch();
    if (!$job || $job['job_status'] !== 'published'
        || ($job['application_deadline'] && $job['application_deadline'] < date('Y-m-d'))
        || $employerAccountStatus !== 'active') {
        throw new RuntimeException('งานนี้ไม่เปิดรับสมัครแล้ว');
    }

    $documentStatement = $pdo->prepare('SELECT document_status FROM employer_documents WHERE employer_user_id=? ORDER BY submitted_at DESC,employer_document_id DESC LIMIT 1');
    $documentStatement->execute([$job['employer_user_id']]);
    if ($documentStatement->fetchColumn() !== 'approved') {
        throw new RuntimeException('งานนี้ไม่เปิดรับสมัครแล้ว');
    }

    $completedStatement = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id=? AND application_status='completed'");
    $completedStatement->execute([$jobId]);
    if ((int) $completedStatement->fetchColumn() >= (int) $job['open_positions']) {
        throw new RuntimeException('งานนี้รับผู้ปฏิบัติงานครบตามจำนวนแล้ว');
    }

    $existing = $pdo->prepare('SELECT application_id,application_status FROM applications WHERE job_id=? AND worker_user_id=? FOR UPDATE');
    $existing->execute([$jobId, user()['id']]);
    $application = $existing->fetch();

    if ($application && $application['application_status'] !== 'withdrawn') {
        throw new RuntimeException('คุณสมัครงานนี้ไปแล้ว');
    }

    $isReapplication = (bool) $application;
    if ($application) {
        $pdo->prepare("UPDATE applications SET application_status='submitted',withdrawn_at=NULL,cover_note=?,resume_file_path=?,created_at=CURRENT_TIMESTAMP WHERE application_id=?")
            ->execute([trim($_POST['cover_note'] ?? ''), $resumeFilePath, $application['application_id']]);
        $appId = (int) $application['application_id'];
        $message = 'ส่งใบสมัครใหม่เรียบร้อยแล้ว ผู้ว่าจ้างจะติดต่อกลับผ่านระบบ';
    } else {
        $pdo->prepare('INSERT INTO applications (job_id,worker_user_id,cover_note,resume_file_path) VALUES (?,?,?,?)')
            ->execute([$jobId, user()['id'], trim($_POST['cover_note'] ?? ''), $resumeFilePath]);
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
