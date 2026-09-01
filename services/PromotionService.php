<?php

declare(strict_types=1);

function promotion_tlv(string $tag, string $value): string
{
    return $tag . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function promotion_crc16(string $payload): string
{
    $crc = 0xFFFF;
    foreach (str_split($payload) as $character) {
        $crc ^= ord($character) << 8;
        for ($bit = 0; $bit < 8; $bit++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function promotion_promptpay_payload(string $promptPayId, float $amount): string
{
    $identifier = preg_replace('/\D+/', '', $promptPayId) ?? '';
    if (strlen($identifier) === 10 && str_starts_with($identifier, '0')) {
        $accountTag = '01';
        $identifier = '0066' . substr($identifier, 1);
    } elseif (strlen($identifier) === 13) {
        $accountTag = '02';
    } else {
        throw new RuntimeException('หมายเลข PromptPay ไม่ถูกต้อง');
    }
    if ($amount <= 0 || $amount > 999999.99) {
        throw new RuntimeException('ยอดชำระไม่ถูกต้อง');
    }

    $merchantAccount = promotion_tlv('00', 'A000000677010111')
        . promotion_tlv($accountTag, $identifier);
    $payload = promotion_tlv('00', '01')
        . promotion_tlv('01', '12')
        . promotion_tlv('29', $merchantAccount)
        . promotion_tlv('53', '764')
        . promotion_tlv('54', number_format($amount, 2, '.', ''))
        . promotion_tlv('58', 'TH')
        . '6304';

    return $payload . promotion_crc16($payload);
}

function promotion_sync_expired(PDO $pdo): void
{
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();
    try {
        $statement = $pdo->query("SELECT jp.promotion_id,jp.employer_user_id,jp.job_id,j.job_title
            FROM job_promotions jp JOIN jobs j ON j.job_id=jp.job_id
            WHERE jp.promotion_status='active' AND jp.ends_at<=NOW() FOR UPDATE");
        $update = $pdo->prepare("UPDATE job_promotions SET promotion_status='expired' WHERE promotion_id=? AND promotion_status='active'");
        foreach ($statement->fetchAll() as $promotion) {
            $update->execute([$promotion['promotion_id']]);
            if (!$update->rowCount()) continue;
            notification_create(
                $pdo,
                (int) $promotion['employer_user_id'],
                'โปรโมชันหมดอายุแล้ว',
                'การโปรโมตงาน “' . $promotion['job_title'] . '” สิ้นสุดแล้ว',
                'employer/promote.php?job=' . $promotion['job_id'] . '&promotion=' . $promotion['promotion_id']
            );
        }
        if ($ownsTransaction) $pdo->commit();
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function promotion_attach_to_jobs(PDO $pdo, array $jobs): array
{
    if (!$jobs) return [];
    $ids = array_values(array_unique(array_filter(array_map(fn(array $job): int => (int) ($job['id'] ?? 0), $jobs))));
    if (!$ids) return $jobs;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare("SELECT jp.job_id,jp.promotion_id,pp.package_code,pp.display_priority
        FROM job_promotions jp JOIN promotion_packages pp ON pp.package_id=jp.package_id
        WHERE jp.job_id IN ({$placeholders}) AND jp.promotion_status='active' AND jp.starts_at<=NOW() AND jp.ends_at>NOW()");
    $statement->execute($ids);
    $active = [];
    foreach ($statement->fetchAll() as $promotion) $active[(int) $promotion['job_id']] = $promotion;
    foreach ($jobs as &$job) {
        $promotion = $active[(int) ($job['id'] ?? 0)] ?? null;
        $job['is_promoted'] = $promotion !== null;
        $job['promotion_code'] = $promotion['package_code'] ?? null;
        $job['promotion_priority'] = (int) ($promotion['display_priority'] ?? 0);
    }
    unset($job);
    return $jobs;
}

function promotion_create_order(PDO $pdo, int $employerId, int $jobId, int $packageId): int
{
    promotion_sync_expired($pdo);
    if (PROMPTPAY_ID === '' || PROMPTPAY_RECIPIENT_NAME === '') {
        throw new RuntimeException('ระบบยังไม่ได้ตั้งค่าบัญชีรับชำระ');
    }

    $pdo->beginTransaction();
    try {
        $jobStatement = $pdo->prepare("SELECT j.job_id
            FROM jobs j
            WHERE j.job_id=? AND j.employer_user_id=? AND j.job_status='published'
              AND j.work_province=? AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())
            FOR UPDATE");
        $jobStatement->execute([$jobId, $employerId, FLEXJOB_PROVINCE]);
        if (!$jobStatement->fetchColumn()) throw new RuntimeException('ประกาศนี้ไม่อยู่ในสถานะที่โปรโมตได้');
        if (!matching_employer_is_verified($pdo, $employerId)) throw new RuntimeException('ต้องยืนยันบัญชีผู้ว่าจ้างก่อนซื้อโปรโมชัน');

        $activeStatement = $pdo->prepare("SELECT promotion_id FROM job_promotions WHERE job_id=? AND promotion_status IN ('pending_verification','active') LIMIT 1 FOR UPDATE");
        $activeStatement->execute([$jobId]);
        if ($activeStatement->fetchColumn()) throw new RuntimeException('ประกาศนี้มีรายการรอตรวจหรือกำลังโปรโมตอยู่แล้ว');

        $packageStatement = $pdo->prepare('SELECT package_id,package_name,price,duration_days FROM promotion_packages WHERE package_id=? AND is_active=1');
        $packageStatement->execute([$packageId]);
        $package = $packageStatement->fetch();
        if (!$package) throw new RuntimeException('ไม่พบแพ็กเกจที่เลือก');

        $pdo->prepare("UPDATE job_promotions SET promotion_status='cancelled' WHERE job_id=? AND employer_user_id=? AND promotion_status='pending_payment'")
            ->execute([$jobId, $employerId]);
        $insert = $pdo->prepare("INSERT INTO job_promotions
            (job_id,employer_user_id,package_id,package_name_snapshot,amount,duration_days,promotion_status)
            VALUES (?,?,?,?,?,?,'pending_payment')");
        $insert->execute([$jobId, $employerId, $package['package_id'], $package['package_name'], $package['price'], $package['duration_days']]);
        $promotionId = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $promotionId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
