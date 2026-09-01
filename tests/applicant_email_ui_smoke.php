<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$fixture = db()->query("SELECT a.application_id,a.job_id,j.employer_user_id,u.first_name,u.last_name,u.email
    FROM applications a
    JOIN jobs j ON j.job_id=a.job_id
    JOIN users u ON u.user_id=j.employer_user_id
    WHERE a.application_status<>'withdrawn' AND u.account_status='active'
    ORDER BY a.application_id
    LIMIT 1")->fetch();

if (!$fixture) {
    echo "applicant email UI smoke test: SKIP (no active application fixture)\n";
    exit;
}

$_SESSION['user'] = [
    'id' => (int) $fixture['employer_user_id'],
    'name' => trim($fixture['first_name'] . ' ' . $fixture['last_name']),
    'role' => 'employer',
    'email' => $fixture['email'],
];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/Flex/employer/applicant-detail.php';
$_GET['id'] = (string) $fixture['application_id'];
$_GET['job'] = (string) $fixture['job_id'];

ob_start();
require APP_ROOT . '/employer/applicant-detail.php';
$html = (string) ob_get_clean();

foreach ([
    'data-bs-target="#applicantEmailModal"',
    'name="action" value="send_email"',
    'name="email_subject"',
    'name="email_message"',
] as $expectedMarkup) {
    if (!str_contains($html, $expectedMarkup)) {
        throw new RuntimeException('Applicant email form markup is incomplete: ' . $expectedMarkup);
    }
}

echo "applicant email UI smoke test: PASS\n";
