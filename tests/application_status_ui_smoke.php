<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$fixture = db()->query("SELECT a.application_id,a.job_id,j.employer_user_id,u.first_name,u.last_name,u.email
    FROM applications a
    JOIN jobs j ON j.job_id=a.job_id
    JOIN users u ON u.user_id=j.employer_user_id
    WHERE a.application_status<>'withdrawn' AND u.account_status='active'
    ORDER BY a.application_id LIMIT 1")->fetch();

if (!$fixture) {
    echo "application status UI smoke test: SKIP (no application fixture)\n";
    exit;
}

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE applications SET application_status='eligible' WHERE application_id=?")
        ->execute([$fixture['application_id']]);

    $_SESSION['user'] = [
        'id' => (int) $fixture['employer_user_id'],
        'name' => trim($fixture['first_name'] . ' ' . $fixture['last_name']),
        'role' => 'employer',
        'email' => $fixture['email'],
    ];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/Flex/employer/applicant-detail.php';
    $_GET = ['id' => (string) $fixture['application_id'], 'job' => (string) $fixture['job_id']];
    $_POST = [];

    ob_start();
    require APP_ROOT . '/employer/applicant-detail.php';
    $html = (string) ob_get_clean();

    foreach (['is-past', 'ผ่านแล้ว', 'is-current', 'สถานะปัจจุบัน', 'is-selectable', 'เลือกได้', 'is-locked', 'ยังไม่ถึงขั้นตอน'] as $expected) {
        if (!str_contains($html, $expected)) {
            throw new RuntimeException('Application status state is missing: ' . $expected);
        }
    }
    if (!preg_match('/<button class="btn btn-primary w-100 mt-3" type="submit" disabled>เลือกสถานะถัดไป<\/button>/', $html)) {
        throw new RuntimeException('Status save button is not disabled before selecting the next stage');
    }

    echo "application status UI smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
