<?php

declare(strict_types=1);

/**
 * Update an application status while distinguishing an unchanged value from a
 * withdrawn application. PDO::rowCount() alone cannot make that distinction
 * because MySQL reports zero affected rows when the new value equals the old.
 *
 * @return bool True when the status changed, false when it was already set.
 */
function application_update_status_by_employer(
    PDO $pdo,
    int $employerId,
    int $jobId,
    int $applicationId,
    string $newStatus
): bool {
    $allowedStatuses = ['submitted', 'eligible', 'interview_passed', 'completed', 'not_selected'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        throw new RuntimeException('สถานะใบสมัครไม่ถูกต้อง');
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();

    try {
        $statement = $pdo->prepare("SELECT a.application_status
            FROM applications a
            JOIN jobs j ON j.job_id=a.job_id
            WHERE a.application_id=? AND a.job_id=? AND j.employer_user_id=?
            FOR UPDATE");
        $statement->execute([$applicationId, $jobId, $employerId]);
        $currentStatus = $statement->fetchColumn();

        if ($currentStatus === false) {
            throw new RuntimeException('ไม่พบใบสมัครที่ต้องการอัปเดต');
        }
        if ($currentStatus === 'withdrawn') {
            throw new RuntimeException('ไม่สามารถเปลี่ยนสถานะใบสมัครที่ผู้หางานถอนแล้ว');
        }
        if ($currentStatus === $newStatus) {
            if ($ownsTransaction) $pdo->commit();
            return false;
        }

        $update = $pdo->prepare('UPDATE applications SET application_status=? WHERE application_id=? AND job_id=?');
        $update->execute([$newStatus, $applicationId, $jobId]);

        if ($ownsTransaction) $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
