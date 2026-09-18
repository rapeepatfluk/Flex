<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$plan = $pdo->query("SELECT * FROM subscription_plans WHERE plan_code='pro-30d' AND is_active=1 LIMIT 1")->fetch();
if (!$plan) throw new RuntimeException('Pro subscription plan was not seeded');
if ((float) $plan['price'] !== 239.0 || (int) $plan['duration_days'] !== 30
    || (int) $plan['active_job_limit'] !== 6 || (int) $plan['promotion_credits'] !== 2
    || (int) $plan['promotion_duration_days'] !== 7) {
    throw new RuntimeException('Pro subscription entitlements are incorrect');
}

$promotionPackageTableCount = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='promotion_packages'")->fetchColumn();
if ($promotionPackageTableCount !== 0) throw new RuntimeException('Standalone promotion package table still exists');
$promotionColumns = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='job_promotions' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
$expectedPromotionColumns = [
    'promotion_id','job_id','employer_user_id','subscription_id','duration_days',
    'promotion_status','starts_at','ends_at','created_at','updated_at',
];
if ($promotionColumns !== $expectedPromotionColumns) {
    throw new RuntimeException('job_promotions still contains standalone payment fields');
}

$payload = promotion_promptpay_payload(PROMPTPAY_ID, 239.0);
if (!str_contains($payload, '010212') || !str_contains($payload, '5303764') || !str_contains($payload, '5406239.00')) {
    throw new RuntimeException('PromptPay payload is missing required dynamic QR fields');
}
$payloadWithoutChecksum = substr($payload, 0, -4);
if (substr($payload, -4) !== promotion_crc16($payloadWithoutChecksum)) {
    throw new RuntimeException('PromptPay payload checksum is invalid');
}

try {
    promotion_promptpay_payload('123', 239);
    throw new RuntimeException('Invalid PromptPay identifier was accepted');
} catch (RuntimeException $exception) {
    if (!str_contains($exception->getMessage(), 'PromptPay')) throw $exception;
}

$fixture = $pdo->query("SELECT job_id,employer_user_id FROM jobs ORDER BY job_id LIMIT 1")->fetch();
if ($fixture) {
    $pdo->beginTransaction();
    try {
        $subscription = $pdo->prepare("INSERT INTO employer_subscriptions
            (employer_user_id,plan_id,plan_name_snapshot,amount,duration_days,active_job_limit,promotion_credits,promotion_duration_days,subscription_status,starts_at,ends_at)
            VALUES (?,?,'Pro smoke test',239,30,6,2,7,'active',NOW(),DATE_ADD(NOW(),INTERVAL 30 DAY))");
        $subscription->execute([$fixture['employer_user_id'],$plan['plan_id']]);
        $subscriptionId = (int) $pdo->lastInsertId();
        $insert = $pdo->prepare("INSERT INTO job_promotions
            (job_id,employer_user_id,subscription_id,duration_days,promotion_status,starts_at,ends_at)
            VALUES (?,?,?,7,'active',DATE_SUB(NOW(),INTERVAL 8 DAY),DATE_SUB(NOW(),INTERVAL 1 DAY))");
        $insert->execute([$fixture['job_id'],$fixture['employer_user_id'],$subscriptionId]);
        $promotionId = (int) $pdo->lastInsertId();
        promotion_sync_expired($pdo);
        $check = $pdo->prepare('SELECT promotion_status FROM job_promotions WHERE promotion_id=?');
        $check->execute([$promotionId]);
        if ($check->fetchColumn() !== 'expired') throw new RuntimeException('Expired promotion remained active');
    } finally {
        $pdo->rollBack();
    }
}

echo "subscription promotion smoke test: PASS\n";
