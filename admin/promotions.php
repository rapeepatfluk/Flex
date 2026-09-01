<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_login('admin');

$pdo = db();
promotion_sync_expired($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $promotionId = (int) ($_POST['promotion_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $note = trim((string) ($_POST['review_note'] ?? ''));
        if (!in_array($decision, ['approve', 'reject'], true)) throw new RuntimeException('ผลการตรวจสอบไม่ถูกต้อง');
        if ($decision === 'reject' && $note === '') throw new RuntimeException('กรุณาระบุเหตุผลที่สลิปไม่ผ่าน');
        if (mb_strlen($note) > 1000) throw new RuntimeException('หมายเหตุยาวเกินไป');

        $pdo->beginTransaction();
        $statement = $pdo->prepare("SELECT jp.*,j.job_title,j.job_status,j.application_deadline
            FROM job_promotions jp JOIN jobs j ON j.job_id=jp.job_id
            WHERE jp.promotion_id=? FOR UPDATE");
        $statement->execute([$promotionId]);
        $promotion = $statement->fetch();
        if (!$promotion || $promotion['promotion_status'] !== 'pending_verification') throw new RuntimeException('รายการนี้ไม่ได้รอตรวจสอบแล้ว');
        if (!$promotion['payment_slip_path']) throw new RuntimeException('รายการไม่มีไฟล์สลิป');

        if ($decision === 'approve') {
            if ($promotion['job_status'] !== 'published' || ($promotion['application_deadline'] && $promotion['application_deadline'] < date('Y-m-d'))) {
                throw new RuntimeException('ประกาศงานปิดรับหรือหมดเขตแล้ว จึงไม่สามารถเปิดโปรโมชันได้');
            }
            $activeStatement = $pdo->prepare("SELECT promotion_id FROM job_promotions WHERE job_id=? AND promotion_status='active' AND promotion_id<>? LIMIT 1 FOR UPDATE");
            $activeStatement->execute([$promotion['job_id'], $promotionId]);
            if ($activeStatement->fetchColumn()) throw new RuntimeException('ประกาศนี้มีโปรโมชันที่กำลังใช้งานอยู่แล้ว');
            $pdo->prepare("UPDATE job_promotions SET promotion_status='active',reviewed_by_user_id=?,reviewed_at=NOW(),review_note=?,starts_at=NOW(),ends_at=DATE_ADD(NOW(),INTERVAL duration_days DAY) WHERE promotion_id=?")
                ->execute([(int) user()['id'], $note ?: null, $promotionId]);
            $message = 'โปรโมชันงาน ' . $promotion['job_title'] . ' เริ่มแล้ว ระยะเวลา ' . (int) $promotion['duration_days'] . ' วัน';
            $success = 'อนุมัติสลิปและเปิดโปรโมชันแล้ว';
        } else {
            $pdo->prepare("UPDATE job_promotions SET promotion_status='rejected',reviewed_by_user_id=?,reviewed_at=NOW(),review_note=? WHERE promotion_id=?")
                ->execute([(int) user()['id'], $note, $promotionId]);
            $message = 'สลิปโปรโมตงาน ' . $promotion['job_title'] . ' ไม่ผ่านการตรวจสอบ — ' . $note;
            $success = 'ปฏิเสธสลิปและแจ้งเหตุผลแล้ว';
        }

        $pdo->prepare('INSERT INTO notifications (user_id,notification_title,notification_message,notification_url) VALUES (?,?,?,?)')
            ->execute([(int) $promotion['employer_user_id'], 'ผลตรวจสลิปโปรโมต', $message, 'employer/promote.php?job=' . $promotion['job_id'] . '&promotion=' . $promotionId]);
        $pdo->commit();
        flash('success', $success);
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Promotion review failed: ' . $e->getMessage());
        flash('error', 'ไม่สามารถบันทึกผลตรวจสลิปได้');
    }
    redirect('admin/promotions.php');
}

$allowedFilters = ['pending_verification','active','rejected','expired','all'];
$filter = in_array((string) ($_GET['status'] ?? ''), $allowedFilters, true) ? (string) $_GET['status'] : 'pending_verification';
$sql = "SELECT jp.*,j.job_title,ep.company_name,CONCAT(u.first_name,' ',u.last_name) employer_name,pp.package_code
    FROM job_promotions jp
    JOIN jobs j ON j.job_id=jp.job_id
    JOIN employer_profiles ep ON ep.user_id=jp.employer_user_id
    JOIN users u ON u.user_id=jp.employer_user_id
    JOIN promotion_packages pp ON pp.package_id=jp.package_id";
$params = [];
if ($filter !== 'all') { $sql .= ' WHERE jp.promotion_status=?'; $params[] = $filter; }
$sql .= " ORDER BY (jp.promotion_status='pending_verification') DESC,COALESCE(jp.payment_submitted_at,jp.created_at) DESC LIMIT 100";
$statement = $pdo->prepare($sql);
$statement->execute($params);
$promotions = $statement->fetchAll();
$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM job_promotions WHERE promotion_status='pending_verification'")->fetchColumn();
$statusMeta = [
    'pending_payment' => ['รอชำระ', 'warning'], 'pending_verification' => ['รอตรวจ', 'info'],
    'active' => ['กำลังโปรโมต', 'success'], 'rejected' => ['ไม่ผ่าน', 'danger'],
    'expired' => ['หมดอายุ', 'secondary'], 'cancelled' => ['ยกเลิก', 'secondary'],
];
$pageTitle = 'ตรวจสลิปโปรโมต | FLEXJOB';
$pageStyles = ['admin-promotions'];
require APP_ROOT . '/partials/header.php';
?>
<main id="content" class="admin-promotions" tabindex="-1"><div class="container">
    <header class="admin-promotions-hero card border-0 mb-4"><div class="card-body p-4 p-lg-5"><div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-4"><div><p class="admin-promotions-eyebrow mb-2">PAYMENT REVIEW</p><h1 class="display-6 mb-2">ตรวจสลิปโปรโมต</h1><p class="text-secondary mb-0">ตรวจผู้รับ ยอดเงิน วันเวลา และเลขอ้างอิงก่อนเปิดโปรโมชัน</p></div><div class="admin-promotions-count"><strong><?= number_format($pendingCount) ?></strong><span>รายการรอตรวจ</span></div></div></div></header>
    <nav class="admin-promotions-tabs mb-4" aria-label="กรองสถานะ"><?php foreach (['pending_verification'=>'รอตรวจ','active'=>'กำลังโปรโมต','rejected'=>'ไม่ผ่าน','expired'=>'หมดอายุ','all'=>'ทั้งหมด'] as $value=>$label): ?><a class="<?= $filter===$value?'is-active':'' ?>" href="?status=<?= e($value) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
    <?php if ($promotions): ?><div class="vstack gap-4"><?php foreach ($promotions as $promotion): $meta=$statusMeta[$promotion['promotion_status']]??['-','secondary']; ?>
        <article class="card border-0 admin-promotion-card"><div class="card-body p-4"><div class="row g-4"><div class="col-xl-7"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><span class="badge text-bg-<?= e($meta[1]) ?> mb-2"><?= e($meta[0]) ?></span><h2 class="h4 mb-1"><?= e($promotion['job_title']) ?></h2><p class="text-secondary mb-0"><?= e($promotion['company_name']) ?> · <?= e($promotion['employer_name']) ?></p></div><strong class="admin-promotion-amount">฿<?= number_format((float)$promotion['amount'],2) ?></strong></div><dl class="admin-promotion-meta"><div><dt>แพ็กเกจ</dt><dd><?= e($promotion['package_name_snapshot']) ?> (<?= (int)$promotion['duration_days'] ?> วัน)</dd></div><div><dt>ส่งสลิป</dt><dd><?= $promotion['payment_submitted_at']?date('d/m/Y H:i',strtotime($promotion['payment_submitted_at'])):'-' ?></dd></div><div><dt>เลขอ้างอิง</dt><dd><?= e($promotion['payment_reference']?:'ไม่ระบุ') ?></dd></div></dl><div class="d-flex flex-wrap gap-2"><?php if($promotion['payment_slip_path']): ?><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=promotion_slip&id=<?= $promotion['promotion_id'] ?>">เปิดสลิป ↗</a><?php endif; ?><a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/job.php?id=<?= $promotion['job_id'] ?>">ดูประกาศ</a></div><?php if($promotion['review_note']): ?><p class="alert alert-light border small mt-3 mb-0"><b>หมายเหตุ:</b> <?= e($promotion['review_note']) ?></p><?php endif; ?></div>
        <div class="col-xl-5"><?php if($promotion['promotion_status']==='pending_verification'): ?><form class="admin-promotion-review" method="post"><?= csrf_field() ?><input type="hidden" name="promotion_id" value="<?= $promotion['promotion_id'] ?>"><label class="form-label" for="note-<?= $promotion['promotion_id'] ?>">หมายเหตุ / เหตุผลที่ไม่ผ่าน</label><textarea class="form-control" id="note-<?= $promotion['promotion_id'] ?>" name="review_note" rows="4" maxlength="1000"></textarea><div class="d-grid d-sm-flex gap-2 mt-3"><button class="btn btn-success flex-fill" type="submit" name="decision" value="approve">อนุมัติและเปิดโปรโมชัน</button><button class="btn btn-outline-danger" type="submit" name="decision" value="reject">ไม่ผ่าน</button></div></form><?php elseif($promotion['promotion_status']==='active'): ?><div class="admin-promotion-active"><b>ระยะเวลาโปรโมต</b><span><?= date('d/m/Y H:i',strtotime($promotion['starts_at'])) ?></span><span>ถึง <?= date('d/m/Y H:i',strtotime($promotion['ends_at'])) ?></span></div><?php endif; ?></div></div></div></article>
    <?php endforeach; ?></div><?php else: ?><div class="card border-0 shadow-sm"><div class="card-body p-5 text-center text-secondary">ไม่มีรายการในสถานะนี้</div></div><?php endif; ?>
</div></main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
