<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$application = $pdo->query("SELECT a.application_id,a.job_id,a.application_status,j.employer_user_id
    FROM applications a
    JOIN jobs j ON j.job_id=a.job_id
    ORDER BY a.application_id
    LIMIT 1")->fetch();

if (!$application) throw new RuntimeException('Smoke test requires at least one application');

$pdo->beginTransaction();
try {
    $applicationId = (int) $application['application_id'];
    $jobId = (int) $application['job_id'];
    $employerId = (int) $application['employer_user_id'];

    $pdo->prepare("UPDATE jobs SET job_status='published',open_positions=1,application_deadline=DATE_ADD(CURDATE(),INTERVAL 7 DAY) WHERE job_id=?")
        ->execute([$jobId]);
    $pdo->prepare("UPDATE users SET account_status='active',email_verified_at=COALESCE(email_verified_at,NOW()) WHERE user_id=?")
        ->execute([$employerId]);
    $documentStatement = $pdo->prepare('SELECT employer_document_id FROM employer_documents WHERE employer_user_id=? ORDER BY submitted_at DESC,employer_document_id DESC LIMIT 1');
    $documentStatement->execute([$employerId]);
    $documentId = (int) $documentStatement->fetchColumn();
    if ($documentId) {
        $pdo->prepare("UPDATE employer_documents SET document_status='approved' WHERE employer_document_id=?")->execute([$documentId]);
    } else {
        $pdo->prepare("INSERT INTO employer_documents (employer_user_id,document_file_path,document_status) VALUES (?,'tests/verification-fixture.pdf','approved')")
            ->execute([$employerId]);
    }
    $pdo->prepare("UPDATE applications SET application_status='not_selected' WHERE job_id=?")
        ->execute([$jobId]);
    $pdo->prepare("UPDATE applications SET application_status='submitted' WHERE application_id=?")
        ->execute([$applicationId]);

    $changed = application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'submitted');
    if ($changed) throw new RuntimeException('Saving an unchanged status was reported as an update');

    $openJobStatement = $pdo->prepare('SELECT COUNT(*) FROM jobs j WHERE j.job_id=? AND ' . application_open_job_sql('j'));
    $openJobStatement->execute([$jobId]);
    if ((int) $openJobStatement->fetchColumn() !== 1) {
        throw new RuntimeException('A job with applications but no completed hire was treated as full');
    }

    if (!application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'eligible')) {
        throw new RuntimeException('Forward transition to eligible was not saved');
    }
    try {
        application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'submitted');
        throw new RuntimeException('Backward transition eligible -> submitted was accepted');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'ข้ามขั้น')) throw $e;
    }

    if (!application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'interview_passed')) {
        throw new RuntimeException('Forward transition to interview_passed was not saved');
    }
    try {
        application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'eligible');
        throw new RuntimeException('Backward transition interview_passed -> eligible was accepted');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'ข้ามขั้น')) throw $e;
    }

    if (!application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'completed')) {
        throw new RuntimeException('Forward transition to completed was not saved');
    }
    $jobStatusStatement = $pdo->prepare('SELECT job_status FROM jobs WHERE job_id=?');
    $jobStatusStatement->execute([$jobId]);
    if ($jobStatusStatement->fetchColumn() !== 'closed') {
        throw new RuntimeException('Job was not closed when completed count reached open_positions');
    }
    try {
        application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'not_selected');
        throw new RuntimeException('Completed terminal status was changed');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'ข้ามขั้น')) throw $e;
    }

    $pdo->prepare("UPDATE applications SET application_status='withdrawn' WHERE application_id=?")
        ->execute([$applicationId]);

    try {
        application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'eligible');
        throw new RuntimeException('A withdrawn application status was changed');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'ถอนแล้ว')) throw $e;
    }

    echo "application status smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
