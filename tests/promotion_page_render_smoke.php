<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (PROMPTPAY_ID === '' || PROMPTPAY_RECIPIENT_NAME === '') {
    echo "promotion page render smoke test: SKIP (payment config missing)\n";
    exit;
}

$pdo = db();
$fixture = $pdo->query("SELECT j.job_id,j.employer_user_id,u.first_name,u.last_name,u.email,pp.package_id,pp.package_name,pp.price,pp.duration_days
    FROM jobs j
    JOIN users u ON u.user_id=j.employer_user_id AND u.account_status='active'
    JOIN employer_documents ed ON ed.employer_user_id=j.employer_user_id AND ed.document_status='approved'
    CROSS JOIN promotion_packages pp
    WHERE j.job_status='published' AND j.work_province='บุรีรัมย์'
      AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())
    ORDER BY j.job_id,pp.sort_order LIMIT 1")->fetch();

if (!$fixture) {
    echo "promotion page render smoke test: SKIP (no verified open job fixture)\n";
    exit;
}

$pdo->beginTransaction();
try {
    $insert = $pdo->prepare("INSERT INTO job_promotions
        (job_id,employer_user_id,package_id,package_name_snapshot,amount,duration_days,promotion_status)
        VALUES (?,?,?,?,?,?,'pending_payment')");
    $insert->execute([
        $fixture['job_id'], $fixture['employer_user_id'], $fixture['package_id'],
        $fixture['package_name'], $fixture['price'], $fixture['duration_days'],
    ]);
    $promotionId = (int) $pdo->lastInsertId();

    $_SESSION['user'] = [
        'id' => (int) $fixture['employer_user_id'],
        'name' => trim($fixture['first_name'] . ' ' . $fixture['last_name']),
        'role' => 'employer',
        'email' => $fixture['email'],
    ];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/Flex/employer/promote.php';
    $_GET = ['job' => (string) $fixture['job_id'], 'promotion' => (string) $promotionId];
    $_POST = [];

    ob_start();
    require APP_ROOT . '/employer/promote.php';
    $html = (string) ob_get_clean();

    foreach (['id="promotionQr"', 'data-payload="', 'สแกนด้วยแอปธนาคาร', 'ส่งสลิปให้ตรวจสอบ'] as $expected) {
        if (!str_contains($html, $expected)) throw new RuntimeException('Rendered promotion page is incomplete: ' . $expected);
    }
    if (str_contains($html, PROMPTPAY_ID)) throw new RuntimeException('Full PromptPay identifier is exposed in the rendered page');

    echo "promotion page render smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
