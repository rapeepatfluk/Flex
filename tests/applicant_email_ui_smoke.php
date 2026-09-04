<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$fixture = db()->query("SELECT a.application_id,a.application_status,a.job_id,j.employer_user_id,u.first_name,u.last_name,u.email
    FROM applications a
    JOIN jobs j ON j.job_id=a.job_id
    JOIN users u ON u.user_id=j.employer_user_id
    WHERE a.application_status<>'withdrawn' AND u.account_status='active'
    ORDER BY a.application_status='eligible' DESC,a.application_id
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
if ($fixture['application_status'] === 'eligible') {
    $_SESSION['flash']['interview_email_prompt'] = '1';
}

ob_start();
require APP_ROOT . '/employer/applicant-detail.php';
$html = (string) ob_get_clean();

if (!preg_match('/href="https:\/\/mail\.google\.com\/mail\/\?view=cm&amp;fs=1&amp;to=[^\"]+&amp;su=[^\"]+&amp;body=[^\"]+"/', $html)) {
    throw new RuntimeException('Applicant Gmail compose link is missing or incomplete');
}
if (!str_contains($html, 'target="_blank"') || !str_contains($html, 'rel="noopener noreferrer"')) {
    throw new RuntimeException('Applicant Gmail compose link does not open safely in a new tab');
}
if (!str_contains($html, 'เปิด Gmail ในแท็บใหม่')) {
    throw new RuntimeException('Applicant email link is missing its accessible label');
}
if ($fixture['application_status'] === 'eligible') {
    foreach (['ผู้สมัครอยู่ในสถานะ “มีสิทธิ์สัมภาษณ์” แล้ว', 'ส่งอีเมลนัดสัมภาษณ์', 'Google%20Meet%20%2F%20Zoom'] as $interviewMarkup) {
        if (!str_contains($html, $interviewMarkup)) {
            throw new RuntimeException('Interview email guidance is incomplete: ' . $interviewMarkup);
        }
    }
}
foreach (['applicantEmailModal', 'name="action" value="send_email"'] as $removedMarkup) {
    if (str_contains($html, $removedMarkup)) {
        throw new RuntimeException('Removed applicant email modal markup is still present: ' . $removedMarkup);
    }
}

echo "applicant email UI smoke test: PASS\n";
