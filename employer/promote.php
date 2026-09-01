<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$employerId = (int) user()['id'];
promotion_sync_expired($pdo);

$jobId = (int) ($_GET['job'] ?? $_POST['job_id'] ?? 0);
$jobStatement = $pdo->prepare("SELECT j.job_id,j.job_title,j.job_status,j.application_deadline,ep.company_name
    FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id
    WHERE j.job_id=? AND j.employer_user_id=?");
$jobStatement->execute([$jobId, $employerId]);
$job = $jobStatement->fetch();
if (!$job) redirect('employer/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'create') {
            $promotionId = promotion_create_order($pdo, $employerId, $jobId, (int) ($_POST['package_id'] ?? 0));
            flash('success', 'สร้างรายการโปรโมตแล้ว สแกน QR เพื่อชำระเงินได้เลย');
            redirect('employer/promote.php?job=' . $jobId . '&promotion=' . $promotionId);
        }

        if ($action === 'upload_slip') {
            $promotionId = (int) ($_POST['promotion_id'] ?? 0);
            $promotionStatement = $pdo->prepare("SELECT promotion_id FROM job_promotions
                WHERE promotion_id=? AND job_id=? AND employer_user_id=? AND promotion_status IN ('pending_payment','rejected')");
            $promotionStatement->execute([$promotionId, $jobId, $employerId]);
            if (!$promotionStatement->fetchColumn()) throw new RuntimeException('รายการนี้ไม่สามารถส่งสลิปได้');

            $reference = trim((string) ($_POST['payment_reference'] ?? ''));
            if (mb_strlen($reference) > 120) throw new RuntimeException('เลขอ้างอิงยาวเกินไป');
            $slipPath = upload_file('payment_slip', ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'payment-slips');
            if (!$slipPath) throw new RuntimeException('กรุณาเลือกไฟล์สลิปการชำระเงิน');

            $pdo->prepare("UPDATE job_promotions SET promotion_status='pending_verification',payment_slip_path=?,payment_reference=?,payment_submitted_at=NOW(),reviewed_by_user_id=NULL,reviewed_at=NULL,review_note=NULL WHERE promotion_id=?")
                ->execute([$slipPath, $reference ?: null, $promotionId]);
            $adminStatement = $pdo->query("SELECT user_id FROM users WHERE role='admin' AND account_status='active'");
            $notify = $pdo->prepare('INSERT INTO notifications (user_id,notification_title,notification_message,notification_url) VALUES (?,?,?,?)');
            foreach ($adminStatement->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
                $notify->execute([(int) $adminId, 'มีสลิปโปรโมตรอตรวจ', $job['company_name'] . ' ส่งสลิปโปรโมตงาน: ' . $job['job_title'], 'admin/promotions.php']);
            }
            flash('success', 'ส่งสลิปแล้ว กรุณารอผู้ดูแลตรวจสอบ');
            redirect('employer/promote.php?job=' . $jobId . '&promotion=' . $promotionId);
        }

        throw new RuntimeException('คำขอไม่ถูกต้อง');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log('Promotion order failed: ' . $e->getMessage());
        flash('error', 'ไม่สามารถดำเนินการโปรโมตได้ กรุณาลองใหม่');
    }
    redirect('employer/promote.php?job=' . $jobId);
}

$packages = $pdo->query('SELECT * FROM promotion_packages WHERE is_active=1 ORDER BY sort_order,package_id')->fetchAll();
$historyStatement = $pdo->prepare("SELECT jp.*,pp.package_code,pp.display_priority
    FROM job_promotions jp JOIN promotion_packages pp ON pp.package_id=jp.package_id
    WHERE jp.job_id=? AND jp.employer_user_id=? ORDER BY jp.created_at DESC LIMIT 10");
$historyStatement->execute([$jobId, $employerId]);
$promotions = $historyStatement->fetchAll();

$promotionId = (int) ($_GET['promotion'] ?? 0);
$promotion = null;
if ($promotionId) {
    foreach ($promotions as $item) {
        if ((int) $item['promotion_id'] === $promotionId) { $promotion = $item; break; }
    }
    if (!$promotion) redirect('employer/promote.php?job=' . $jobId);
}

$statusMeta = [
    'pending_payment' => ['รอชำระเงิน', 'warning'],
    'pending_verification' => ['รอตรวจสลิป', 'info'],
    'active' => ['กำลังโปรโมต', 'success'],
    'rejected' => ['สลิปไม่ผ่าน', 'danger'],
    'expired' => ['หมดอายุ', 'secondary'],
    'cancelled' => ['ยกเลิกแล้ว', 'secondary'],
];
$qrPayload = $promotion && in_array($promotion['promotion_status'], ['pending_payment', 'rejected'], true)
    ? promotion_promptpay_payload(PROMPTPAY_ID, (float) $promotion['amount'])
    : null;
$maskedPromptPay = PROMPTPAY_ID !== '' ? str_repeat('•', max(0, strlen(PROMPTPAY_ID) - 4)) . substr(PROMPTPAY_ID, -4) : '-';
$hasBlockingPromotion = array_filter($promotions, fn(array $item): bool => in_array($item['promotion_status'], ['pending_verification', 'active'], true));
$canPromote = !$hasBlockingPromotion
    && $job['job_status'] === 'published'
    && ($job['application_deadline'] === null || $job['application_deadline'] >= date('Y-m-d'))
    && matching_employer_is_verified($pdo, $employerId);

$pageTitle = 'โปรโมตประกาศงาน | FLEXJOB';
$pageStyles = ['promotion'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container promotion-page py-4 py-lg-5">
    <a class="promotion-back" href="<?= BASE_URL ?>/employer/dashboard.php#all-jobs">← กลับไปจัดการประกาศ</a>
    <section class="promotion-hero card border-0 shadow-sm mt-3 mb-4"><div class="card-body p-4 p-lg-5"><p class="promotion-eyebrow mb-2">PROMOTE YOUR JOB</p><h1 class="display-6 mb-2">โปรโมต <?= e($job['job_title']) ?></h1><p class="mb-0 text-secondary">เพิ่มการมองเห็นในหน้าค้นหางาน ชำระด้วย PromptPay และรอผู้ดูแลตรวจสลิป</p></div></section>

    <?php if (!$canPromote): ?><div class="alert alert-warning"><?= $hasBlockingPromotion ? 'ประกาศนี้มีรายการรอตรวจหรือกำลังโปรโมตอยู่แล้ว' : 'โปรโมตได้เฉพาะประกาศที่กำลังเปิดรับและบัญชีผู้ว่าจ้างที่ยืนยันแล้ว' ?></div><?php endif; ?>

    <?php if ($promotion): $currentMeta = $statusMeta[$promotion['promotion_status']] ?? ['-', 'secondary']; ?>
        <section class="row g-4 mb-5">
            <div class="col-lg-7">
                <article class="card border-0 shadow-sm h-100 promotion-payment-card"><div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><div><p class="promotion-eyebrow mb-1">PAYMENT</p><h2 class="h3 mb-0"><?= e($promotion['package_name_snapshot']) ?></h2></div><span class="badge rounded-pill text-bg-<?= e($currentMeta[1]) ?> align-self-start"><?= e($currentMeta[0]) ?></span></div>
                    <?php if ($qrPayload): ?>
                        <div class="promotion-payment-layout">
                            <div class="promotion-qr-shell"><div id="promotionQr" data-payload="<?= e($qrPayload) ?>" aria-label="QR PromptPay ยอด <?= number_format((float) $promotion['amount'], 2) ?> บาท"></div><small>สแกนด้วยแอปธนาคาร</small></div>
                            <div><span class="promotion-amount-label">ยอดชำระ</span><strong class="promotion-amount">฿<?= number_format((float) $promotion['amount'], 2) ?></strong><dl class="promotion-payment-meta"><div><dt>ผู้รับ</dt><dd><?= e(PROMPTPAY_RECIPIENT_NAME) ?></dd></div><div><dt>PromptPay</dt><dd><?= e($maskedPromptPay) ?></dd></div><div><dt>ระยะเวลาโปรโมต</dt><dd><?= (int) $promotion['duration_days'] ?> วันหลังอนุมัติ</dd></div></dl><p class="small text-secondary mb-0">QR นี้กำหนดยอดเงินให้อัตโนมัติ โปรดตรวจชื่อผู้รับก่อนยืนยัน</p></div>
                        </div>
                    <?php elseif ($promotion['promotion_status'] === 'pending_verification'): ?><div class="promotion-state text-center"><span>◷</span><h3>กำลังรอตรวจสลิป</h3><p>ผู้ดูแลจะตรวจสอบยอดเงินและแจ้งผลผ่านระบบ</p></div>
                    <?php elseif ($promotion['promotion_status'] === 'active'): ?><div class="promotion-state is-active text-center"><span>✓</span><h3>ประกาศกำลังโปรโมต</h3><p><?= date('d/m/Y H:i', strtotime($promotion['starts_at'])) ?> – <?= date('d/m/Y H:i', strtotime($promotion['ends_at'])) ?></p></div>
                    <?php else: ?><div class="promotion-state text-center"><span>•</span><h3><?= e($currentMeta[0]) ?></h3></div><?php endif; ?>
                </div></article>
            </div>
            <div class="col-lg-5">
                <article class="card border-0 shadow-sm h-100"><div class="card-body p-4">
                    <p class="promotion-eyebrow mb-1">PAYMENT PROOF</p><h2 class="h4 mb-3">อัปโหลดสลิป</h2>
                    <?php if ($promotion['promotion_status'] === 'rejected' && $promotion['review_note']): ?><div class="alert alert-danger small"><b>เหตุผลที่ไม่ผ่าน:</b> <?= e($promotion['review_note']) ?></div><?php endif; ?>
                    <?php if (in_array($promotion['promotion_status'], ['pending_payment','rejected'], true)): ?>
                        <form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_slip"><input type="hidden" name="job_id" value="<?= $jobId ?>"><input type="hidden" name="promotion_id" value="<?= $promotion['promotion_id'] ?>"><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="paymentSlip">ไฟล์สลิป</label><input class="form-control" id="paymentSlip" name="payment_slip" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required><div class="form-text">JPG, PNG, WebP หรือ PDF ไม่เกิน 8 MB</div></div><div class="mb-3"><label class="form-label" for="paymentReference">เลขอ้างอิง (ถ้ามี)</label><input class="form-control" id="paymentReference" name="payment_reference" maxlength="120"></div><button class="btn btn-primary w-100" type="submit">ส่งสลิปให้ตรวจสอบ</button></form>
                    <?php else: ?><p class="text-secondary mb-0">สลิปถูกส่งเข้าระบบแล้ว</p><?php endif; ?>
                </div></article>
            </div>
        </section>
    <?php endif; ?>

    <section aria-labelledby="promotionPackagesHeading"><div class="mb-3"><p class="promotion-eyebrow mb-1">PACKAGES</p><h2 class="h3 mb-0" id="promotionPackagesHeading">เลือกแพ็กเกจโปรโมต</h2></div><div class="row g-4">
        <?php foreach ($packages as $package): ?><div class="col-md-6"><article class="card border-0 shadow-sm h-100 promotion-package <?= $package['package_code'] === 'featured-7d' ? 'is-featured' : '' ?>"><div class="card-body p-4 p-lg-5"><?php if ($package['package_code'] === 'featured-7d'): ?><span class="promotion-popular">แนะนำ</span><?php endif; ?><h3 class="h4"><?= e($package['package_name']) ?></h3><p class="text-secondary"><?= e($package['package_description']) ?></p><strong class="promotion-package-price">฿<?= number_format((float) $package['price'], 0) ?></strong><small>/ <?= (int) $package['duration_days'] ?> วัน</small><form class="mt-4" method="post"><input type="hidden" name="action" value="create"><input type="hidden" name="job_id" value="<?= $jobId ?>"><input type="hidden" name="package_id" value="<?= $package['package_id'] ?>"><?= csrf_field() ?><button class="btn <?= $package['package_code'] === 'featured-7d' ? 'btn-primary' : 'btn-outline-primary' ?> w-100" type="submit" <?= $canPromote ? '' : 'disabled' ?>>เลือกแพ็กเกจนี้</button></form></div></article></div><?php endforeach; ?>
    </div></section>

    <?php if ($promotions): ?><section class="card border-0 shadow-sm mt-5"><div class="card-body p-4"><h2 class="h4 mb-3">ประวัติการโปรโมต</h2><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>แพ็กเกจ</th><th>ยอด</th><th>สถานะ</th><th>วันที่</th><th></th></tr></thead><tbody><?php foreach ($promotions as $item): $meta = $statusMeta[$item['promotion_status']] ?? ['-', 'secondary']; ?><tr><td><?= e($item['package_name_snapshot']) ?></td><td>฿<?= number_format((float) $item['amount'], 2) ?></td><td><span class="badge text-bg-<?= e($meta[1]) ?>"><?= e($meta[0]) ?></span></td><td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/employer/promote.php?job=<?= $jobId ?>&promotion=<?= $item['promotion_id'] ?>">ดูรายการ</a></td></tr><?php endforeach; ?></tbody></table></div></div></section><?php endif; ?>
</main>
<?php if ($qrPayload): ?><script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><script>(function(){var target=document.getElementById('promotionQr');if(!target||typeof QRCode==='undefined')return;new QRCode(target,{text:target.dataset.payload,width:260,height:260,colorDark:'#071b34',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});})();</script><?php endif; ?>
<?php require APP_ROOT . '/partials/footer.php'; ?>
