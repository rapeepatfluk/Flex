<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$profiles = $pdo->query("SELECT user_id,skills FROM worker_profiles WHERE skills IS NOT NULL AND TRIM(skills)<>''")->fetchAll();
$pdo->beginTransaction();
try {
    foreach ($profiles as $profile) {
        matching_sync_worker_skills($pdo, (int) $profile['user_id'], (string) $profile['skills']);
    }
    $pdo->commit();
    echo 'Backfilled structured skills for ' . count($profiles) . " worker profile(s).\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
