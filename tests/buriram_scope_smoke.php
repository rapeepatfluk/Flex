<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$workerId = (int) $pdo->query("SELECT user_id FROM users WHERE role='worker' AND account_status='active' ORDER BY user_id LIMIT 1")->fetchColumn();
if (!$workerId) throw new RuntimeException('Smoke test requires at least one active worker');

foreach (matching_jobs_for_worker($pdo, $workerId, 200) as $job) {
    if (($job['work_province'] ?? '') !== FLEXJOB_PROVINCE) {
        throw new RuntimeException('Worker recommendations included a job outside Buriram');
    }
}

$worker = [
    'work_province' => FLEXJOB_PROVINCE,
    'preferred_work_mode' => 'any',
    'skill_ids' => null,
    'preference_category_ids' => null,
];
$localScore = matching_calculate(['work_province' => FLEXJOB_PROVINCE, 'work_mode' => 'onsite'], $worker)['score'];
$outsideScore = matching_calculate(['work_province' => 'กรุงเทพมหานคร', 'work_mode' => 'onsite'], $worker)['score'];
if ($localScore !== $outsideScore) throw new RuntimeException('Province still affects the matching score');

$outsideJob = $pdo->query("SELECT job_id,employer_user_id FROM jobs WHERE COALESCE(work_province,'')<>'บุรีรัมย์' ORDER BY job_id LIMIT 1")->fetch();
if ($outsideJob && matching_workers_for_job($pdo, (int) $outsideJob['job_id'], (int) $outsideJob['employer_user_id'])) {
    throw new RuntimeException('An employer could match candidates to a job outside Buriram');
}

echo "buriram scope smoke test: PASS\n";
