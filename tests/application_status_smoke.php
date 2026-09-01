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

    $pdo->prepare("UPDATE applications SET application_status='submitted' WHERE application_id=?")
        ->execute([$applicationId]);

    $changed = application_update_status_by_employer($pdo, $employerId, $jobId, $applicationId, 'submitted');
    if ($changed) throw new RuntimeException('Saving an unchanged status was reported as an update');

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
