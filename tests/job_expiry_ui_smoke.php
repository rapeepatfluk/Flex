<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$fixture = $pdo->query("SELECT j.job_id,j.job_title,j.employer_user_id,u.first_name,u.last_name,u.email
    FROM jobs j
    JOIN users u ON u.user_id=j.employer_user_id AND u.account_status='active'
    JOIN employer_profiles ep ON ep.user_id=j.employer_user_id
    WHERE j.job_status='published' AND j.application_deadline<CURDATE()
    ORDER BY j.job_id LIMIT 1")->fetch();

if (!$fixture) {
    echo "job expiry UI smoke test: SKIP (no expired published job fixture)\n";
    exit;
}

$pdo->beginTransaction();
try {
    $_SESSION['user'] = [
        'id' => (int) $fixture['employer_user_id'],
        'name' => trim($fixture['first_name'] . ' ' . $fixture['last_name']),
        'role' => 'employer',
        'email' => $fixture['email'],
    ];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/Flex/employer/dashboard.php';
    $_GET = [];
    $_POST = [];

    ob_start();
    require APP_ROOT . '/employer/dashboard.php';
    $html = (string) ob_get_clean();

    preg_match_all('/<article class="employer-job-row.*?<\/article>/s', $html, $rows);
    $escapedTitle = e($fixture['job_title']);
    $expiredRow = null;
    foreach ($rows[0] as $row) {
        if (str_contains($row, $escapedTitle)) {
            $expiredRow = $row;
            break;
        }
    }

    if ($expiredRow === null) throw new RuntimeException('Expired job row was not rendered');
    if (!str_contains($expiredRow, 'หมดเขตรับสมัคร')) throw new RuntimeException('Expired job still shows a published status');
    if (str_contains($expiredRow, '/employer/promote.php')) throw new RuntimeException('Expired job still exposes the promote action');
    if (str_contains($expiredRow, '/employer/candidates.php')) throw new RuntimeException('Expired job still exposes candidate search');

    echo "job expiry UI smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
