<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$workerId = (int) $pdo->query("SELECT user_id FROM users WHERE role='worker' AND account_status='active' ORDER BY user_id LIMIT 1")->fetchColumn();
$job = $pdo->query("SELECT job_id,employer_user_id,work_province,job_category_id FROM jobs WHERE job_status='published' ORDER BY job_id LIMIT 1")->fetch();

if (!$workerId || !$job) throw new RuntimeException('Smoke test requires at least one active worker and one published job');

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE worker_profiles SET profile_visibility='searchable',work_province=?,preferred_work_mode='any' WHERE user_id=?")
        ->execute([$job['work_province'], $workerId]);
    matching_sync_worker_skills($pdo, $workerId, 'FLEXJOB Smoke Skill');
    matching_sync_job_skills($pdo, (int) $job['job_id'], 'FLEXJOB Smoke Skill', 'FLEXJOB Preferred Skill');

    $categoryStmt = $pdo->prepare('SELECT category_slug FROM job_categories WHERE job_category_id=?');
    $categoryStmt->execute([$job['job_category_id']]);
    matching_sync_worker_preferences($pdo, $workerId, [(string) $categoryStmt->fetchColumn()]);

    $recommendations = matching_jobs_for_worker($pdo, $workerId, 200);
    $recommendation = array_values(array_filter($recommendations, fn(array $item): bool => (int) $item['id'] === (int) $job['job_id']))[0] ?? null;
    if (!$recommendation || ($recommendation['match']['score'] ?? 0) < 70) throw new RuntimeException('Worker recommendation score was not calculated correctly');

    $candidates = matching_workers_for_job($pdo, (int) $job['job_id'], (int) $job['employer_user_id']);
    $candidate = array_values(array_filter($candidates, fn(array $item): bool => (int) $item['user_id'] === $workerId))[0] ?? null;
    if (!$candidate || ($candidate['match']['score'] ?? 0) < 70) throw new RuntimeException('Employer candidate score was not calculated correctly');

    echo "matching smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
