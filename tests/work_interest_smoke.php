<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$interests = matching_work_interests($pdo);
if (count($interests) !== 10) throw new RuntimeException('Expected exactly 10 active MVP work interests');

$workerId = (int) $pdo->query("SELECT user_id FROM users WHERE role='worker' AND account_status='active' ORDER BY user_id LIMIT 1")->fetchColumn();
if (!$workerId) throw new RuntimeException('Smoke test requires at least one active worker');

$interestIds = array_map(fn(array $interest): int => (int) $interest['work_interest_id'], $interests);
$pdo->beginTransaction();
try {
    matching_sync_worker_work_interests($pdo, $workerId, array_slice($interestIds, 0, 5));
    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM worker_work_interests WHERE worker_user_id=?');
    $countStatement->execute([$workerId]);
    if ((int) $countStatement->fetchColumn() !== 5) throw new RuntimeException('Worker work interests were not saved correctly');

    try {
        matching_sync_worker_work_interests($pdo, $workerId, array_slice($interestIds, 0, 6));
        throw new RuntimeException('More than five work interests were accepted');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'More than five work interests were accepted') throw $exception;
    }

    try {
        matching_sync_worker_work_interests($pdo, $workerId, [999999999]);
        throw new RuntimeException('An unknown work-interest ID was accepted');
    } catch (RuntimeException $exception) {
        if ($exception->getMessage() === 'An unknown work-interest ID was accepted') throw $exception;
    }

    $matchingJob = [
        'work_interest_id' => $interestIds[0],
        'work_interest_name' => $interests[0]['interest_name'],
        'work_mode' => 'remote',
    ];
    $matchingWorker = [
        'work_interest_ids' => (string) $interestIds[0],
        'preferred_work_mode' => 'remote',
        'work_province' => FLEXJOB_PROVINCE,
    ];
    $match = matching_calculate($matchingJob, $matchingWorker);
    if ($match['score'] !== 100) throw new RuntimeException('Work-interest score was not calculated correctly');
    if (!array_filter($match['reasons'], fn(string $reason): bool => str_contains($reason, 'สนใจงานด้าน'))) throw new RuntimeException('Work-interest reason is missing');

    $employerId = (int) $pdo->query("SELECT user_id FROM users WHERE role='employer' AND account_status='active' ORDER BY user_id LIMIT 1")->fetchColumn();
    $categoryId = (int) $pdo->query('SELECT job_category_id FROM job_categories ORDER BY job_category_id LIMIT 1')->fetchColumn();
    if (!$employerId || !$categoryId) throw new RuntimeException('Smoke test requires an employer and job category');
    $pdo->prepare('INSERT INTO jobs (employer_user_id,job_category_id,work_interest_id,job_title,job_description,work_location,work_province,work_schedule,work_mode,application_deadline,pay_amount,pay_unit,open_positions) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$employerId, $categoryId, $interestIds[0], 'FLEXJOB Work Interest Smoke', 'Temporary smoke-test job', 'อำเภอเมืองบุรีรัมย์', FLEXJOB_PROVINCE, 'ทดสอบ', 'onsite', null, 500, 'day', 1]);
    $jobId = (int) $pdo->lastInsertId();
    $jobInterestStatement = $pdo->prepare('SELECT work_interest_id FROM jobs WHERE job_id=?');
    $jobInterestStatement->execute([$jobId]);
    if ((int) $jobInterestStatement->fetchColumn() !== $interestIds[0]) throw new RuntimeException('Job work interest was not saved correctly');

    echo "work interest smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
