<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';

$failures = [];
$checks = [];

$check = static function (bool $passed, string $label, string $details = '') use (&$checks, &$failures): void {
    $checks[] = [$passed, $label, $details];
    if (!$passed) $failures[] = $label;
};

$check(PHP_VERSION_ID >= 80300, 'PHP 8.3 or newer', PHP_VERSION);

foreach (['fileinfo', 'mbstring', 'openssl', 'PDO', 'pdo_mysql', 'session'] as $extension) {
    $check(extension_loaded($extension), "PHP extension: {$extension}");
}

try {
    $pdo = db();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $serverVersion = (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $isMySql = $driver === 'mysql' && stripos($serverVersion, 'mariadb') === false;
    $majorVersion = (int) preg_replace('/^\D*(\d+).*/', '$1', $serverVersion);

    $check($isMySql, 'Database engine: MySQL', "{$driver} {$serverVersion}");
    $check($isMySql && $majorVersion >= 8, 'MySQL 8.x or newer', $serverVersion);
    $check((string) $pdo->query('SELECT @@character_set_database')->fetchColumn() === 'utf8mb4', 'Database character set: utf8mb4');
} catch (Throwable $exception) {
    $check(false, 'Database connection', $exception->getMessage());
}

foreach ($checks as [$passed, $label, $details]) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $label;
    if ($details !== '') echo " ({$details})";
    echo PHP_EOL;
}

exit($failures === [] ? 0 : 1);
