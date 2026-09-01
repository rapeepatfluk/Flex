<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$packages = $pdo->query("SELECT package_code,price,duration_days FROM promotion_packages ORDER BY sort_order,package_id")->fetchAll();
if (count($packages) < 2) throw new RuntimeException('Promotion packages were not seeded');

$expected = [
    'boost-3d' => ['price' => 99.0, 'days' => 3],
    'featured-7d' => ['price' => 199.0, 'days' => 7],
];
foreach ($packages as $package) {
    if (!isset($expected[$package['package_code']])) continue;
    $target = $expected[$package['package_code']];
    if ((float) $package['price'] !== $target['price'] || (int) $package['duration_days'] !== $target['days']) {
        throw new RuntimeException('Promotion package values are incorrect: ' . $package['package_code']);
    }
    unset($expected[$package['package_code']]);
}
if ($expected) throw new RuntimeException('Required promotion package is missing');

foreach ([99.0 => '540599.00', 199.0 => '5406199.00'] as $amount => $amountTag) {
    $payload = promotion_promptpay_payload(PROMPTPAY_ID, (float) $amount);
    if (!str_contains($payload, '010212') || !str_contains($payload, '5303764') || !str_contains($payload, $amountTag)) {
        throw new RuntimeException('PromptPay payload is missing required dynamic QR fields');
    }
    $payloadWithoutChecksum = substr($payload, 0, -4);
    if (substr($payload, -4) !== promotion_crc16($payloadWithoutChecksum)) {
        throw new RuntimeException('PromptPay payload checksum is invalid');
    }
}

try {
    promotion_promptpay_payload('123', 99);
    throw new RuntimeException('Invalid PromptPay identifier was accepted');
} catch (RuntimeException $e) {
    if (!str_contains($e->getMessage(), 'PromptPay')) throw $e;
}

$fixture = $pdo->query("SELECT j.job_id,j.employer_user_id,pp.package_id,pp.package_name,pp.price,pp.duration_days
    FROM jobs j CROSS JOIN promotion_packages pp
    ORDER BY j.job_id,pp.package_id LIMIT 1")->fetch();
if ($fixture) {
    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare("INSERT INTO job_promotions
            (job_id,employer_user_id,package_id,package_name_snapshot,amount,duration_days,promotion_status,starts_at,ends_at)
            VALUES (?,?,?,?,?,?,'active',DATE_SUB(NOW(),INTERVAL 2 DAY),DATE_SUB(NOW(),INTERVAL 1 DAY))");
        $insert->execute([
            $fixture['job_id'], $fixture['employer_user_id'], $fixture['package_id'],
            $fixture['package_name'], $fixture['price'], $fixture['duration_days'],
        ]);
        $promotionId = (int) $pdo->lastInsertId();
        promotion_sync_expired($pdo);
        $check = $pdo->prepare('SELECT promotion_status FROM job_promotions WHERE promotion_id=?');
        $check->execute([$promotionId]);
        if ($check->fetchColumn() !== 'expired') throw new RuntimeException('Expired promotion remained active');
    } finally {
        $pdo->rollBack();
    }
}

echo "promotion smoke test: PASS\n";
