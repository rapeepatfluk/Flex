<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

if (($composer['require']['php'] ?? null) !== '^8.3') throw new RuntimeException('PHP 8.3 requirement is missing');
foreach (['ext-fileinfo', 'ext-mbstring', 'ext-openssl', 'ext-pdo', 'ext-pdo_mysql', 'ext-session'] as $extension) {
    if (!isset($composer['require'][$extension])) throw new RuntimeException("Missing requirement: {$extension}");
}

$header = (string) file_get_contents($root . '/partials/header.php');
if (!str_contains($header, 'bootstrap@5.3.3')) throw new RuntimeException('Bootstrap 5.x is not configured');

$phpMailer = (string) file_get_contents($root . '/lib/phpmailer/PHPMailer.php');
if (!preg_match("/const VERSION = '6\\.9\\.1'/", $phpMailer)) throw new RuntimeException('PHPMailer 6.9.1 is not configured');

$config = (string) file_get_contents($root . '/config/config.php');
foreach (['FLEXJOB_APP_URL', 'FLEXJOB_DB_HOST', 'FLEXJOB_DB_PORT', 'FLEXJOB_DB_NAME', 'FLEXJOB_DB_USER', 'FLEXJOB_DB_PASS'] as $variable) {
    if (!str_contains($config, $variable)) throw new RuntimeException("Missing environment variable support: {$variable}");
}

putenv('FLEXJOB_APP_URL=https://jobs.example.ac.th/flexjob');
require_once $root . '/config/config.php';
if (app_url('auth/verify.php?token=test') !== 'https://jobs.example.ac.th/flexjob/auth/verify.php?token=test') {
    throw new RuntimeException('Configured HTTPS application URL is not preserved');
}
putenv('FLEXJOB_APP_URL');

$urlSources = '';
foreach ([$root . '/auth', $root . '/config'] as $directory) {
    foreach (glob($directory . '/*.php') ?: [] as $file) $urlSources .= file_get_contents($file);
}
if (preg_match("/'http:\\/\\/'\\s*\\.\\s*.*HTTP_HOST/", $urlSources)) throw new RuntimeException('Hard-coded HTTP application URL remains');

$apacheRules = (string) file_get_contents($root . '/.htaccess');
foreach (['database', 'config', 'partials', 'tmp', 'Flexjob', 'X-Content-Type-Options'] as $expected) {
    if (!str_contains($apacheRules, $expected)) throw new RuntimeException("Apache protection is missing: {$expected}");
}

echo "server_spec_smoke.php: PASS\n";
