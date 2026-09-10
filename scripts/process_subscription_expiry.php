<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

require_once __DIR__ . '/../config/config.php';

try {
    subscription_sync_statuses(db());
    promotion_sync_expired(db());
    echo "Subscription and promotion expiry processed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Subscription expiry failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
