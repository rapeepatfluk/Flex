<?php

declare(strict_types=1);

/**
 * Shared SQL predicate for a job that can accept a new application or
 * invitation. open_positions is a hiring target, not an application limit.
 */
function application_open_job_sql(string $jobAlias = 'j'): string
{
    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $jobAlias)) {
        throw new InvalidArgumentException('Invalid job table alias');
    }

    return "{$jobAlias}.job_status='published'
        AND ({$jobAlias}.application_deadline IS NULL OR {$jobAlias}.application_deadline>=CURDATE())
        AND EXISTS (
            SELECT 1 FROM users application_employer
            WHERE application_employer.user_id={$jobAlias}.employer_user_id
              AND application_employer.account_status='active'
        )
        AND COALESCE((
            SELECT application_document.document_status
            FROM employer_documents application_document
            WHERE application_document.employer_user_id={$jobAlias}.employer_user_id
            ORDER BY application_document.submitted_at DESC, application_document.employer_document_id DESC
            LIMIT 1
        ), 'not_submitted')='approved'
        AND (
            SELECT COUNT(*) FROM applications completed_application
            WHERE completed_application.job_id={$jobAlias}.job_id
              AND completed_application.application_status='completed'
        ) < {$jobAlias}.open_positions";
}

function application_allowed_statuses_from(string $currentStatus): array
{
    $transitions = [
        'submitted' => ['eligible', 'not_selected'],
        'eligible' => ['interview_passed', 'not_selected'],
        'interview_passed' => ['completed', 'not_selected'],
        'not_selected' => [],
        'completed' => [],
        'withdrawn' => [],
    ];
    return array_values(array_unique(array_merge([$currentStatus], $transitions[$currentStatus] ?? [])));
}

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
        // Always lock the job before an application. Applying and completing
        // candidates use the same lock order, preventing capacity races.
        $pdo->prepare('SELECT user_id FROM users WHERE user_id=? FOR UPDATE')->execute([$employerId]);
        $jobStatement = $pdo->prepare('SELECT open_positions FROM jobs WHERE job_id=? AND employer_user_id=? FOR UPDATE');
        $jobStatement->execute([$jobId, $employerId]);
        $openPositions = $jobStatement->fetchColumn();
        if ($openPositions === false) {
            throw new RuntimeException('ไม่พบประกาศงาน');
        }

        $statement = $pdo->prepare('SELECT application_status FROM applications WHERE application_id=? AND job_id=? FOR UPDATE');
        $statement->execute([$applicationId, $jobId]);
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

        if (!in_array($newStatus, application_allowed_statuses_from((string) $currentStatus), true)) {
            throw new RuntimeException('ไม่สามารถเปลี่ยนสถานะข้ามขั้นหรือแก้ไขงานที่เสร็จสิ้นแล้วได้');
        }

        if ($newStatus === 'completed') {
            $completedStatement = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id=? AND application_status='completed'");
            $completedStatement->execute([$jobId]);
            if ((int) $completedStatement->fetchColumn() >= (int) $openPositions) {
                throw new RuntimeException('ประกาศนี้รับผู้ปฏิบัติงานครบตามจำนวนแล้ว');
            }
        }

        if ($newStatus === 'completed') {
            $update = $pdo->prepare("UPDATE applications
                SET application_status='completed',completed_at=COALESCE(completed_at,NOW())
                WHERE application_id=? AND job_id=?");
            $update->execute([$applicationId, $jobId]);
        } else {
            $update = $pdo->prepare('UPDATE applications SET application_status=? WHERE application_id=? AND job_id=?');
            $update->execute([$newStatus, $applicationId, $jobId]);
        }

        if ($newStatus === 'completed') {
            $completedStatement = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id=? AND application_status='completed'");
            $completedStatement->execute([$jobId]);
            if ((int) $completedStatement->fetchColumn() >= (int) $openPositions) {
                $pdo->prepare("UPDATE jobs SET job_status='closed' WHERE job_id=?")->execute([$jobId]);
            }
        }

        if ($ownsTransaction) $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
