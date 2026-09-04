<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

require_once __DIR__ . '/../config/config.php';

$limit = isset($argv[1]) ? (int) $argv[1] : 20;

try {
    $result = process_email_queue(db(), $limit);
    echo sprintf(
        "Email queue processed: %d sent, %d queued for retry, %d failed.\n",
        $result['sent'],
        $result['retried'],
        $result['failed']
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'Email queue failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
