<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (PROMPTPAY_ID === '' || PROMPTPAY_RECIPIENT_NAME === '') {
    echo "subscription page render smoke test: SKIP (payment config missing)\n";
    exit;
}

$pdo = db();
$fixture = $pdo->query("SELECT u.user_id,u.first_name,u.last_name,u.email,sp.*
    FROM users u
    JOIN employer_documents ed ON ed.employer_user_id=u.user_id AND ed.document_status='approved'
    CROSS JOIN subscription_plans sp
    WHERE u.role='employer' AND u.account_status='active' AND sp.plan_code='pro-30d' AND sp.is_active=1
    ORDER BY u.user_id LIMIT 1")->fetch();
if (!$fixture) {
    echo "subscription page render smoke test: SKIP (no verified employer fixture)\n";
    exit;
}

$pdo->beginTransaction();
try {
    $insert = $pdo->prepare("INSERT INTO employer_subscriptions
        (employer_user_id,plan_id,plan_name_snapshot,amount,duration_days,active_job_limit,promotion_credits,promotion_duration_days,subscription_status)
        VALUES (?,?,?,?,?,?,?,?,'pending_payment')");
    $insert->execute([$fixture['user_id'],$fixture['plan_id'],$fixture['plan_name'],$fixture['price'],$fixture['duration_days'],$fixture['active_job_limit'],$fixture['promotion_credits'],$fixture['promotion_duration_days']]);
    $subscriptionId = (int) $pdo->lastInsertId();

    $_SESSION['user'] = [
        'id' => (int) $fixture['user_id'],
        'name' => trim($fixture['first_name'] . ' ' . $fixture['last_name']),
        'role' => 'employer',
        'email' => $fixture['email'],
    ];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/Flex/employer/subscription.php';
    $_GET = ['subscription' => (string) $subscriptionId];
    $_POST = [];

    ob_start();
    require APP_ROOT . '/employer/subscription.php';
    $html = (string) ob_get_clean();
    foreach (['id="promotionQr"','data-payload="','สแกนด้วยแอปธนาคาร','ส่งสลิปให้ตรวจสอบ','฿239.00'] as $expected) {
        if (!str_contains($html, $expected)) throw new RuntimeException('Rendered subscription page is incomplete: ' . $expected);
    }
    if (str_contains($html, PROMPTPAY_ID)) throw new RuntimeException('Full PromptPay identifier is exposed in the rendered page');
    echo "subscription page render smoke test: PASS\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
