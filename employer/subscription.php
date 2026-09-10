<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');
$pdo = db();
$employerId = (int) user()['id'];
subscription_sync_statuses($pdo);
promotion_sync_expired($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create') {
            $id = subscription_create_order($pdo,$employerId,(int)($_POST['plan_id'] ?? 0));
            flash('success','สร้างรายการแล้ว กรุณาชำระเงินและอัปโหลดสลิป');
            redirect('employer/subscription.php?subscription=' . $id);
        }
        if ($action === 'upload_slip') {
            $id = (int) ($_POST['subscription_id'] ?? 0);
            $statement = $pdo->prepare("SELECT subscription_id FROM employer_subscriptions WHERE subscription_id=? AND employer_user_id=? AND subscription_status IN ('pending_payment','rejected')");
            $statement->execute([$id,$employerId]);
            if (!$statement->fetchColumn()) throw new RuntimeException('รายการนี้ไม่สามารถส่งสลิปได้');
            $slip = upload_file('payment_slip',['jpg','jpeg','png','webp','pdf'],'payment-slips');
            if (!$slip) throw new RuntimeException('กรุณาเลือกไฟล์สลิปการชำระเงิน');
            $reference = trim((string) ($_POST['payment_reference'] ?? ''));
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE employer_subscriptions SET subscription_status='pending_verification',payment_slip_path=?,payment_reference=?,payment_submitted_at=NOW(),reviewed_by_user_id=NULL,reviewed_at=NULL,review_note=NULL WHERE subscription_id=?")
                ->execute([$slip,$reference ?: null,$id]);
            foreach ($pdo->query("SELECT user_id FROM users WHERE role='admin' AND account_status='active'")->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
                notification_create($pdo,(int)$adminId,'มีสลิปแพ็กเกจ Pro รอตรวจ',user()['name'] . ' ส่งสลิปแพ็กเกจ Pro','admin/subscriptions.php');
            }
            $pdo->commit();
            flash('success','ส่งสลิปแล้ว กรุณารอผู้ดูแลตรวจสอบ');
            redirect('employer/subscription.php?subscription=' . $id);
        }
        if ($action === 'use_promotion') {
            subscription_use_promotion($pdo,$employerId,(int)($_POST['job_id'] ?? 0));
            flash('success','เริ่มโปรโมตประกาศแล้ว สิทธิ์มีอายุ 7 วัน');
            redirect('employer/subscription.php');
        }
        if ($action === 'keep_free_jobs') {
            $selected = array_values(array_unique(array_map('intval',(array)($_POST['job_ids'] ?? []))));
            if (count($selected) > FREE_ACTIVE_JOB_LIMIT) throw new RuntimeException('เลือกเปิดต่อได้ไม่เกิน 3 ประกาศ');
            $pdo->beginTransaction();
            $openStatement = $pdo->prepare("SELECT job_id FROM jobs WHERE employer_user_id=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE()) FOR UPDATE");
            $openStatement->execute([$employerId]);
            $openIds = array_map('intval',$openStatement->fetchAll(PDO::FETCH_COLUMN));
            if (array_diff($selected,$openIds)) throw new RuntimeException('รายการประกาศไม่ถูกต้อง');
            $hide = array_values(array_diff($openIds,$selected));
            if ($hide) {
                $placeholders = implode(',',array_fill(0,count($hide),'?'));
                $pdo->prepare("UPDATE jobs SET job_status='hidden' WHERE employer_user_id=? AND job_id IN ({$placeholders})")->execute(array_merge([$employerId],$hide));
            }
            $pdo->commit();
            flash('success','บันทึกประกาศที่จะเปิดต่อในสิทธิ์ Free แล้ว');
            redirect('employer/dashboard.php#all-jobs');
        }
        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error',$exception->getMessage());
        redirect('employer/subscription.php');
    }
}

$plans = $pdo->query('SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY sort_order,plan_id')->fetchAll();
$historyStatement = $pdo->prepare('SELECT es.*,sp.plan_code FROM employer_subscriptions es JOIN subscription_plans sp ON sp.plan_id=es.plan_id WHERE es.employer_user_id=? ORDER BY es.created_at DESC,es.subscription_id DESC');
$historyStatement->execute([$employerId]);
$subscriptions = $historyStatement->fetchAll();
$selectedId = (int) ($_GET['subscription'] ?? 0);
$selectedSubscription = null;
foreach ($subscriptions as $item) if ((int)$item['subscription_id'] === $selectedId) $selectedSubscription = $item;
$entitlements = subscription_entitlements($pdo,$employerId);
$current = $entitlements['subscription'];
$jobStatement = $pdo->prepare("SELECT j.job_id,j.job_title,j.application_deadline,j.job_status,
    EXISTS(SELECT 1 FROM job_promotions jp WHERE jp.job_id=j.job_id AND jp.promotion_status='active' AND jp.starts_at<=NOW() AND jp.ends_at>NOW()) is_promoted
    FROM jobs j WHERE j.employer_user_id=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.updated_at DESC");
$jobStatement->execute([$employerId]);
$openJobs = $jobStatement->fetchAll();
$pendingVerification = array_filter($subscriptions,fn(array $s):bool=>$s['subscription_status']==='pending_verification');
$statusMeta = ['pending_payment'=>['รอชำระ','warning'],'pending_verification'=>['รอตรวจสลิป','info'],'active'=>['อนุมัติแล้ว','success'],'rejected'=>['สลิปไม่ผ่าน','danger'],'expired'=>['หมดอายุ','secondary'],'cancelled'=>['ยกเลิก','secondary']];
$qrPayload = $selectedSubscription && in_array($selectedSubscription['subscription_status'],['pending_payment','rejected'],true)
    ? promotion_promptpay_payload(PROMPTPAY_ID,(float)$selectedSubscription['amount']) : null;
$promptPayDigits = preg_replace('/\D+/','',PROMPTPAY_ID) ?: '';
$maskedPromptPay = strlen($promptPayDigits)>4 ? str_repeat('•',strlen($promptPayDigits)-4) . substr($promptPayDigits,-4) : $promptPayDigits;
$pageTitle = 'แพ็กเกจผู้ว่าจ้าง | FLEXJOB';
$pageStyles = ['promotion'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container promotion-page py-4 py-lg-5">
    <a class="promotion-back" href="<?= BASE_URL ?>/employer/dashboard.php">← กลับสู่แดชบอร์ด</a>
    <section class="promotion-hero card border-0 shadow-sm mt-3 mb-4"><div class="card-body p-4 p-lg-5"><p class="promotion-eyebrow mb-2">EMPLOYER PLAN</p><h1 class="display-6 mb-2">แพ็กเกจ <?= e($entitlements['plan']) ?></h1><p class="mb-0 text-secondary">เปิดรับ <?= $entitlements['open_jobs'] ?>/<?= $entitlements['active_job_limit'] ?> ประกาศ<?php if($current): ?> · ใช้โปรโมต <?= $entitlements['promotions_used'] ?>/<?= $entitlements['promotion_credits'] ?> ครั้ง · หมดอายุ <?= date('d/m/Y H:i',strtotime($current['ends_at'])) ?><?php endif ?></p></div></section>

    <?php if (!$current && $entitlements['open_jobs'] > FREE_ACTIVE_JOB_LIMIT): ?>
        <section class="alert alert-warning mb-4"><h2 class="h5">เลือกประกาศที่จะเปิดต่อใน Free</h2><p>คุณมี <?= $entitlements['open_jobs'] ?> ประกาศ เลือกได้ไม่เกิน 3 รายการ รายการอื่นจะถูกซ่อนแต่ไม่ถูกลบ</p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="keep_free_jobs"><?php foreach($openJobs as $job): ?><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="job_ids[]" value="<?= (int)$job['job_id'] ?>"><span class="form-check-label"><?= e($job['job_title']) ?></span></label><?php endforeach ?><button class="btn btn-warning mt-2" type="submit">ยืนยันประกาศ Free</button></form></section>
    <?php endif ?>

    <?php if ($selectedSubscription): $meta=$statusMeta[$selectedSubscription['subscription_status']]??['-','secondary']; ?>
        <section class="card border-0 shadow-sm mb-5"><div class="card-body p-4 p-lg-5"><div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><div><p class="promotion-eyebrow mb-1">PAYMENT</p><h2 class="h3 mb-0"><?= e($selectedSubscription['plan_name_snapshot']) ?></h2></div><span class="badge rounded-pill text-bg-<?= e($meta[1]) ?> align-self-start"><?= e($meta[0]) ?></span></div>
        <?php if(in_array($selectedSubscription['subscription_status'],['pending_payment','rejected'],true)): ?><div class="row g-4"><div class="col-lg-6"><div class="promotion-qr-shell mx-auto"><div id="promotionQr" data-payload="<?= e((string)$qrPayload) ?>"></div><small>สแกนด้วยแอปธนาคาร</small></div><p class="text-center mt-3"><strong class="promotion-amount">฿<?= number_format((float)$selectedSubscription['amount'],2) ?></strong><br>ผู้รับ <?= e(PROMPTPAY_RECIPIENT_NAME) ?> · <?= e($maskedPromptPay) ?></p></div><div class="col-lg-6"><?php if($selectedSubscription['review_note']): ?><div class="alert alert-danger"><?= e($selectedSubscription['review_note']) ?></div><?php endif ?><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="upload_slip"><input type="hidden" name="subscription_id" value="<?= (int)$selectedSubscription['subscription_id'] ?>"><label class="form-label" for="paymentSlip">ไฟล์สลิป</label><input class="form-control mb-3" id="paymentSlip" name="payment_slip" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required><label class="form-label" for="paymentReference">เลขอ้างอิง (ถ้ามี)</label><input class="form-control mb-3" id="paymentReference" name="payment_reference" maxlength="120"><button class="btn btn-primary w-100">ส่งสลิปให้ตรวจสอบ</button></form></div></div>
        <?php elseif($selectedSubscription['subscription_status']==='pending_verification'): ?><div class="promotion-state text-center"><span>◷</span><h3>กำลังรอตรวจสลิป</h3><p>ผู้ดูแลจะตรวจสอบและเริ่มแพ็กเกจให้หลังอนุมัติ</p></div>
        <?php elseif($selectedSubscription['subscription_status']==='active'): ?><div class="promotion-state is-active text-center"><span>✓</span><h3>ชำระเงินผ่านแล้ว</h3><p><?= date('d/m/Y H:i',strtotime($selectedSubscription['starts_at'])) ?> – <?= date('d/m/Y H:i',strtotime($selectedSubscription['ends_at'])) ?></p></div><?php endif ?></div></section>
    <?php endif ?>

    <?php if ($current && $entitlements['promotions_used'] < $entitlements['promotion_credits']): ?>
        <section class="card border-0 shadow-sm mb-5"><div class="card-body p-4 p-lg-5"><p class="promotion-eyebrow mb-1">PROMOTION CREDITS</p><h2 class="h3">ใช้สิทธิ์โปรโมตประกาศ</h2><p class="text-secondary">เหลือ <?= $entitlements['promotion_credits']-$entitlements['promotions_used'] ?> ครั้ง ครั้งละ <?= $entitlements['promotion_duration_days'] ?> วัน</p><div class="vstack gap-2"><?php foreach($openJobs as $job): ?><form method="post" class="d-flex justify-content-between align-items-center border rounded-3 p-3"><?= csrf_field() ?><input type="hidden" name="action" value="use_promotion"><input type="hidden" name="job_id" value="<?= (int)$job['job_id'] ?>"><span><?= e($job['job_title']) ?></span><button class="btn btn-sm btn-outline-success" type="submit" <?= $job['is_promoted']?'disabled':'' ?>><?= $job['is_promoted']?'กำลังโปรโมต':'โปรโมต 7 วัน' ?></button></form><?php endforeach ?></div></div></section>
    <?php endif ?>

    <section aria-labelledby="plansHeading"><p class="promotion-eyebrow mb-1">30-DAY PLAN</p><h2 class="h3" id="plansHeading"><?= $current?'ต่ออายุแพ็กเกจ':'อัปเกรดจาก Free' ?></h2><div class="row g-4 mt-1"><?php foreach($plans as $plan): ?><div class="col-lg-6"><article class="card border-0 shadow-sm h-100 promotion-package is-featured"><div class="card-body p-4 p-lg-5"><span class="promotion-popular">แนะนำ</span><h3><?= e($plan['plan_name']) ?></h3><p class="text-secondary"><?= e($plan['plan_description']) ?></p><strong class="promotion-package-price">฿<?= number_format((float)$plan['price'],0) ?></strong><small>/ <?= (int)$plan['duration_days'] ?> วัน</small><ul class="mt-4"><li>เปิดรับพร้อมกัน <?= (int)$plan['active_job_limit'] ?> ประกาศ</li><li>โปรโมต <?= (int)$plan['promotion_credits'] ?> ครั้ง ครั้งละ <?= (int)$plan['promotion_duration_days'] ?> วัน</li><li>ไม่มีการตัดเงินอัตโนมัติ</li></ul><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create"><input type="hidden" name="plan_id" value="<?= (int)$plan['plan_id'] ?>"><button class="btn btn-primary w-100" <?= $pendingVerification?'disabled':'' ?>><?= $current?'ต่ออายุ 30 วัน':'เลือกแพ็กเกจนี้' ?></button></form></div></article></div><?php endforeach ?></div></section>

    <?php if($subscriptions): ?><section class="card border-0 shadow-sm mt-5"><div class="card-body p-4"><h2 class="h4">ประวัติแพ็กเกจ</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>แพ็กเกจ</th><th>ยอด</th><th>สถานะ</th><th>วันที่</th><th></th></tr></thead><tbody><?php foreach($subscriptions as $item): $meta=$statusMeta[$item['subscription_status']]??['-','secondary']; ?><tr><td><?= e($item['plan_name_snapshot']) ?></td><td>฿<?= number_format((float)$item['amount'],2) ?></td><td><span class="badge text-bg-<?= e($meta[1]) ?>"><?= e($meta[0]) ?></span></td><td><?= date('d/m/Y H:i',strtotime($item['created_at'])) ?></td><td><a class="btn btn-sm btn-outline-primary" href="?subscription=<?= (int)$item['subscription_id'] ?>">ดูรายการ</a></td></tr><?php endforeach ?></tbody></table></div></div></section><?php endif ?>
</main>
<?php if($qrPayload): ?><script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>(function(){var t=document.getElementById('promotionQr');if(t&&window.QRCode)new QRCode(t,{text:t.dataset.payload,width:260,height:260,colorDark:'#071b34',colorLight:'#fff',correctLevel:QRCode.CorrectLevel.M});})();</script><?php endif ?>
<?php require APP_ROOT . '/partials/footer.php'; ?>
