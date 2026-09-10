<?php

declare(strict_types=1);

const FREE_ACTIVE_JOB_LIMIT = 3;
const SUBSCRIPTION_DOWNGRADE_GRACE_DAYS = 3;

function subscription_sync_statuses(PDO $pdo): void
{
    $expired = $pdo->query("SELECT subscription_id,employer_user_id,plan_name_snapshot,ends_at
        FROM employer_subscriptions WHERE subscription_status='active' AND ends_at<=NOW()")->fetchAll();
    if ($expired) {
        $update = $pdo->prepare("UPDATE employer_subscriptions SET subscription_status='expired' WHERE subscription_id=? AND subscription_status='active' AND ends_at<=NOW()");
        foreach ($expired as $subscription) {
            $update->execute([$subscription['subscription_id']]);
            if ($update->rowCount()) {
                notification_create($pdo, (int) $subscription['employer_user_id'], 'แพ็กเกจ Pro หมดอายุแล้ว', 'แพ็กเกจ ' . $subscription['plan_name_snapshot'] . ' หมดอายุแล้ว บัญชีกลับสู่สิทธิ์ Free', 'employer/subscription.php');
            }
        }
    }

    // Enforce Free limits for every employer without an active plan. Employers
    // whose paid plan just expired retain the documented grace period; accounts
    // that never subscribed are Free immediately.
    $freeEmployers = $pdo->query("SELECT u.user_id,
            (SELECT MAX(es.ends_at) FROM employer_subscriptions es
             WHERE es.employer_user_id=u.user_id AND es.subscription_status='expired') last_ended_at
        FROM users u WHERE u.role='employer'")->fetchAll();
    foreach ($freeEmployers as $row) {
        $employerId = (int) $row['user_id'];
        $current = subscription_current($pdo, $employerId);
        if ($current) continue;
        if ($row['last_ended_at'] && strtotime((string) $row['last_ended_at']) > strtotime('-' . SUBSCRIPTION_DOWNGRADE_GRACE_DAYS . ' days')) continue;
        $jobs = $pdo->prepare("SELECT job_id FROM jobs WHERE employer_user_id=? AND job_status='published'
            AND (application_deadline IS NULL OR application_deadline>=CURDATE()) ORDER BY updated_at DESC,job_id DESC");
        $jobs->execute([$employerId]);
        $ids = array_map('intval', $jobs->fetchAll(PDO::FETCH_COLUMN));
        $hideIds = array_slice($ids, FREE_ACTIVE_JOB_LIMIT);
        if (!$hideIds) continue;
        $placeholders = implode(',', array_fill(0, count($hideIds), '?'));
        $pdo->prepare("UPDATE jobs SET job_status='hidden' WHERE employer_user_id=? AND job_id IN ({$placeholders})")
            ->execute(array_merge([$employerId], $hideIds));
        notification_create($pdo, $employerId, 'ปรับประกาศตามสิทธิ์ Free', 'ระบบซ่อนประกาศส่วนเกินตามสิทธิ์ Free คุณสามารถเลือกเปิดประกาศได้ไม่เกิน 3 รายการ', 'employer/dashboard.php#all-jobs');
    }
}

function subscription_current(PDO $pdo, int $employerId): ?array
{
    $statement = $pdo->prepare("SELECT es.*,sp.plan_code FROM employer_subscriptions es
        JOIN subscription_plans sp ON sp.plan_id=es.plan_id
        WHERE es.employer_user_id=? AND es.subscription_status='active' AND es.starts_at<=NOW() AND es.ends_at>NOW()
        ORDER BY es.ends_at DESC,es.subscription_id DESC LIMIT 1");
    $statement->execute([$employerId]);
    return $statement->fetch() ?: null;
}

function subscription_open_job_count(PDO $pdo, int $employerId): int
{
    $statement = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_user_id=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
    $statement->execute([$employerId]);
    return (int) $statement->fetchColumn();
}

/** @return array{plan:string,active_job_limit:int,promotion_credits:int,promotion_duration_days:int,subscription:?array,open_jobs:int,promotions_used:int} */
function subscription_entitlements(PDO $pdo, int $employerId): array
{
    $subscription = subscription_current($pdo, $employerId);
    $used = 0;
    if ($subscription) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM job_promotions WHERE subscription_id=?');
        $statement->execute([$subscription['subscription_id']]);
        $used = (int) $statement->fetchColumn();
    }
    return [
        'plan' => $subscription ? $subscription['plan_name_snapshot'] : 'Free',
        'active_job_limit' => $subscription ? (int) $subscription['active_job_limit'] : FREE_ACTIVE_JOB_LIMIT,
        'promotion_credits' => $subscription ? (int) $subscription['promotion_credits'] : 0,
        'promotion_duration_days' => $subscription ? (int) $subscription['promotion_duration_days'] : 0,
        'subscription' => $subscription,
        'open_jobs' => subscription_open_job_count($pdo, $employerId),
        'promotions_used' => $used,
    ];
}

function subscription_assert_can_publish_job(PDO $pdo, int $employerId, ?int $excludedJobId = null): void
{
    $subscription = subscription_current($pdo, $employerId);
    $limit = $subscription ? (int) $subscription['active_job_limit'] : FREE_ACTIVE_JOB_LIMIT;
    $sql = "SELECT COUNT(*) FROM jobs WHERE employer_user_id=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())";
    $params = [$employerId];
    if ($excludedJobId) { $sql .= ' AND job_id<>?'; $params[] = $excludedJobId; }
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    if ((int) $statement->fetchColumn() >= $limit) {
        $label = $subscription ? 'แพ็กเกจ Pro' : 'สิทธิ์ Free';
        throw new RuntimeException("{$label} เปิดรับพร้อมกันได้ไม่เกิน {$limit} ประกาศ กรุณาปิดประกาศเดิมหรืออัปเกรดแพ็กเกจ");
    }
}

function subscription_create_order(PDO $pdo, int $employerId, int $planId): int
{
    subscription_sync_statuses($pdo);
    if (PROMPTPAY_ID === '' || PROMPTPAY_RECIPIENT_NAME === '') throw new RuntimeException('ระบบชำระเงินยังไม่เปิดให้บริการ');
    if (!matching_employer_is_verified($pdo, $employerId)) throw new RuntimeException('ต้องยืนยันบัญชีผู้ว่าจ้างก่อนซื้อแพ็กเกจ');

    $pdo->beginTransaction();
    try {
        $employerLock = $pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='employer' FOR UPDATE");
        $employerLock->execute([$employerId]);
        if (!$employerLock->fetchColumn()) throw new RuntimeException('ไม่พบบัญชีผู้ว่าจ้าง');
        $planStatement = $pdo->prepare('SELECT * FROM subscription_plans WHERE plan_id=? AND is_active=1 FOR UPDATE');
        $planStatement->execute([$planId]);
        $plan = $planStatement->fetch();
        if (!$plan) throw new RuntimeException('ไม่พบแพ็กเกจที่เลือก');
        $pending = $pdo->prepare("SELECT subscription_id FROM employer_subscriptions WHERE employer_user_id=? AND subscription_status='pending_verification' LIMIT 1 FOR UPDATE");
        $pending->execute([$employerId]);
        if ($pending->fetchColumn()) throw new RuntimeException('คุณมีรายการชำระเงินที่กำลังรอตรวจสอบอยู่แล้ว');
        $pdo->prepare("UPDATE employer_subscriptions SET subscription_status='cancelled' WHERE employer_user_id=? AND subscription_status='pending_payment'")->execute([$employerId]);
        $insert = $pdo->prepare("INSERT INTO employer_subscriptions
            (employer_user_id,plan_id,plan_name_snapshot,amount,duration_days,active_job_limit,promotion_credits,promotion_duration_days,subscription_status)
            VALUES (?,?,?,?,?,?,?,?,'pending_payment')");
        $insert->execute([$employerId,$plan['plan_id'],$plan['plan_name'],$plan['price'],$plan['duration_days'],$plan['active_job_limit'],$plan['promotion_credits'],$plan['promotion_duration_days']]);
        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

function subscription_use_promotion(PDO $pdo, int $employerId, int $jobId): int
{
    promotion_sync_expired($pdo);
    subscription_sync_statuses($pdo);
    $pdo->beginTransaction();
    try {
        $subscriptionStatement = $pdo->prepare("SELECT * FROM employer_subscriptions WHERE employer_user_id=? AND subscription_status='active' AND starts_at<=NOW() AND ends_at>NOW() ORDER BY ends_at DESC LIMIT 1 FOR UPDATE");
        $subscriptionStatement->execute([$employerId]);
        $subscription = $subscriptionStatement->fetch();
        if (!$subscription) throw new RuntimeException('ต้องมีแพ็กเกจ Pro ที่ใช้งานอยู่จึงจะโปรโมตประกาศได้');
        $usedStatement = $pdo->prepare('SELECT COUNT(*) FROM job_promotions WHERE subscription_id=?');
        $usedStatement->execute([$subscription['subscription_id']]);
        if ((int) $usedStatement->fetchColumn() >= (int) $subscription['promotion_credits']) throw new RuntimeException('คุณใช้สิทธิ์โปรโมตครบแล้วสำหรับรอบนี้');

        $jobStatement = $pdo->prepare("SELECT j.job_id FROM jobs j WHERE j.job_id=? AND j.employer_user_id=? AND " . application_open_job_sql('j') . " FOR UPDATE");
        $jobStatement->execute([$jobId,$employerId]);
        if (!$jobStatement->fetchColumn()) throw new RuntimeException('ประกาศนี้ไม่อยู่ในสถานะที่โปรโมตได้');
        $activeStatement = $pdo->prepare("SELECT promotion_id FROM job_promotions WHERE job_id=? AND promotion_status='active' AND starts_at<=NOW() AND ends_at>NOW() LIMIT 1 FOR UPDATE");
        $activeStatement->execute([$jobId]);
        if ($activeStatement->fetchColumn()) throw new RuntimeException('ประกาศนี้กำลังโปรโมตอยู่แล้ว');
        $packageStatement = $pdo->query("SELECT package_id,package_name,duration_days FROM promotion_packages WHERE package_code='pro-credit-7d' LIMIT 1");
        $package = $packageStatement->fetch();
        if (!$package) throw new RuntimeException('ไม่พบสิทธิ์โปรโมตของแพ็กเกจ Pro');
        $duration = (int) $subscription['promotion_duration_days'];
        $insert = $pdo->prepare("INSERT INTO job_promotions
            (job_id,employer_user_id,subscription_id,promotion_source,package_id,package_name_snapshot,amount,duration_days,promotion_status,starts_at,ends_at)
            VALUES (?,?,?,'subscription',?,?,0,?,'active',NOW(),DATE_ADD(NOW(),INTERVAL ? DAY))");
        $insert->execute([$jobId,$employerId,$subscription['subscription_id'],$package['package_id'],$package['package_name'],$duration,$duration]);
        $promotionId = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $promotionId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

function subscription_review_payment(PDO $pdo, int $subscriptionId, int $adminId, string $decision, string $note): array
{
    if (!in_array($decision, ['approve','reject'], true)) throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    if ($decision === 'reject' && trim($note) === '') throw new RuntimeException('กรุณาระบุเหตุผลที่ไม่ผ่าน');
    $ownerStatement = $pdo->prepare('SELECT employer_user_id FROM employer_subscriptions WHERE subscription_id=?');
    $ownerStatement->execute([$subscriptionId]);
    $employerId = (int) $ownerStatement->fetchColumn();
    if (!$employerId) throw new RuntimeException('ไม่พบรายการชำระเงิน');
    $pdo->beginTransaction();
    try {
        $pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='employer' FOR UPDATE")
            ->execute([$employerId]);
        $statement = $pdo->prepare("SELECT * FROM employer_subscriptions WHERE subscription_id=? AND subscription_status='pending_verification' FOR UPDATE");
        $statement->execute([$subscriptionId]);
        $subscription = $statement->fetch();
        if (!$subscription) throw new RuntimeException('รายการนี้ไม่ได้อยู่ในสถานะรอตรวจสอบ');
        if (!$subscription['payment_slip_path']) throw new RuntimeException('รายการไม่มีไฟล์สลิป');
        if ($decision === 'approve') {
            $endStatement = $pdo->prepare("SELECT MAX(ends_at) FROM employer_subscriptions WHERE employer_user_id=? AND subscription_id<>? AND subscription_status='active' AND ends_at>NOW()");
            $endStatement->execute([$subscription['employer_user_id'],$subscriptionId]);
            $latestEnd = $endStatement->fetchColumn();
            $startsAt = $latestEnd && strtotime((string) $latestEnd) > time() ? (string) $latestEnd : date('Y-m-d H:i:s');
            $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +' . (int) $subscription['duration_days'] . ' days'));
            $pdo->prepare("UPDATE employer_subscriptions SET subscription_status='active',reviewed_by_user_id=?,reviewed_at=NOW(),review_note=?,starts_at=?,ends_at=? WHERE subscription_id=?")
                ->execute([$adminId,trim($note) ?: null,$startsAt,$endsAt,$subscriptionId]);
            $message = 'อนุมัติแพ็กเกจ ' . $subscription['plan_name_snapshot'] . ' แล้ว ใช้งานตั้งแต่ ' . date('d/m/Y H:i',strtotime($startsAt)) . ' ถึง ' . date('d/m/Y H:i',strtotime($endsAt));
        } else {
            $pdo->prepare("UPDATE employer_subscriptions SET subscription_status='rejected',reviewed_by_user_id=?,reviewed_at=NOW(),review_note=? WHERE subscription_id=?")
                ->execute([$adminId,trim($note),$subscriptionId]);
            $message = 'สลิปแพ็กเกจไม่ผ่านการตรวจสอบ — ' . trim($note);
        }
        notification_create($pdo,(int) $subscription['employer_user_id'],'ผลตรวจสลิปแพ็กเกจ Pro',$message,'employer/subscription.php?subscription=' . $subscriptionId);
        $pdo->commit();
        return ['employer_user_id'=>(int)$subscription['employer_user_id'],'message'=>$message];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}
