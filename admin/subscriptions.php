<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');
$pdo = db();
subscription_sync_statuses($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        subscription_review_payment($pdo,(int)($_POST['subscription_id'] ?? 0),(int)user()['id'],(string)($_POST['decision'] ?? ''),(string)($_POST['review_note'] ?? ''));
        flash('success','บันทึกผลตรวจสลิปแล้ว');
    } catch (Throwable $exception) {
        flash('error',$exception->getMessage());
    }
    redirect('admin/subscriptions.php');
}

$allowed = ['pending_verification','active','rejected','expired','all'];
$filter = in_array((string)($_GET['status'] ?? ''),$allowed,true) ? (string)$_GET['status'] : 'pending_verification';
$sql = "SELECT es.*,sp.plan_code,ep.company_name,CONCAT(u.first_name,' ',u.last_name) employer_name
    FROM employer_subscriptions es
    JOIN subscription_plans sp ON sp.plan_id=es.plan_id
    JOIN employer_profiles ep ON ep.user_id=es.employer_user_id
    JOIN users u ON u.user_id=es.employer_user_id";
$params=[];
if($filter!=='all'){ $sql.=' WHERE es.subscription_status=?'; $params[]=$filter; }
$sql.=" ORDER BY (es.subscription_status='pending_verification') DESC,COALESCE(es.payment_submitted_at,es.created_at) DESC LIMIT 100";
$statement=$pdo->prepare($sql);$statement->execute($params);$subscriptions=$statement->fetchAll();
$pendingCount=(int)$pdo->query("SELECT COUNT(*) FROM employer_subscriptions WHERE subscription_status='pending_verification'")->fetchColumn();
$statusMeta=['pending_payment'=>['รอชำระ','warning'],'pending_verification'=>['รอตรวจ','info'],'active'=>['อนุมัติแล้ว','success'],'rejected'=>['ไม่ผ่าน','danger'],'expired'=>['หมดอายุ','secondary'],'cancelled'=>['ยกเลิก','secondary']];
$pageTitle='ตรวจสลิปแพ็กเกจ | FLEXJOB';
$pageStyles=['admin-promotions'];
require APP_ROOT . '/partials/header.php';
?>
<main id="content" class="admin-promotions" tabindex="-1"><div class="container">
    <header class="admin-promotions-hero card border-0 mb-4"><div class="card-body p-4 p-lg-5"><div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-4"><div><p class="admin-promotions-eyebrow mb-2">SUBSCRIPTION PAYMENT</p><h1 class="display-6 mb-2">ตรวจสลิปแพ็กเกจ Pro</h1><p class="text-secondary mb-0">ตรวจผู้รับ ยอดเงิน วันเวลา และเลขอ้างอิงก่อนเริ่มแพ็กเกจ 30 วัน</p></div><div class="admin-promotions-count"><strong><?= number_format($pendingCount) ?></strong><span>รายการรอตรวจ</span></div></div></div></header>
    <nav class="admin-promotions-tabs mb-4" aria-label="กรองสถานะ"><?php foreach(['pending_verification'=>'รอตรวจ','active'=>'อนุมัติแล้ว','rejected'=>'ไม่ผ่าน','expired'=>'หมดอายุ','all'=>'ทั้งหมด'] as $value=>$label): ?><a class="<?= $filter===$value?'is-active':'' ?>" href="?status=<?= e($value) ?>"><?= e($label) ?></a><?php endforeach ?></nav>
    <?php if($subscriptions): ?><div class="vstack gap-4"><?php foreach($subscriptions as $subscription): $meta=$statusMeta[$subscription['subscription_status']]??['-','secondary']; ?><article class="card border-0 admin-promotion-card"><div class="card-body p-4"><div class="row g-4"><div class="col-xl-7"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><span class="badge text-bg-<?= e($meta[1]) ?> mb-2"><?= e($meta[0]) ?></span><h2 class="h4 mb-1"><?= e($subscription['company_name']) ?></h2><p class="text-secondary mb-0"><?= e($subscription['employer_name']) ?></p></div><strong class="admin-promotion-amount">฿<?= number_format((float)$subscription['amount'],2) ?></strong></div><dl class="admin-promotion-meta"><div><dt>แพ็กเกจ</dt><dd><?= e($subscription['plan_name_snapshot']) ?> (<?= (int)$subscription['duration_days'] ?> วัน)</dd></div><div><dt>สิทธิ์</dt><dd><?= (int)$subscription['active_job_limit'] ?> ประกาศ · โปรโมต <?= (int)$subscription['promotion_credits'] ?> ครั้ง</dd></div><div><dt>ส่งสลิป</dt><dd><?= $subscription['payment_submitted_at']?date('d/m/Y H:i',strtotime($subscription['payment_submitted_at'])):'-' ?></dd></div><div><dt>เลขอ้างอิง</dt><dd><?= e($subscription['payment_reference']?:'ไม่ระบุ') ?></dd></div></dl><?php if($subscription['payment_slip_path']): ?><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=subscription_slip&amp;id=<?= (int)$subscription['subscription_id'] ?>">เปิดสลิป ↗</a><?php endif ?><?php if($subscription['starts_at']): ?><p class="alert alert-light border small mt-3 mb-0"><?= date('d/m/Y H:i',strtotime($subscription['starts_at'])) ?> – <?= date('d/m/Y H:i',strtotime($subscription['ends_at'])) ?></p><?php endif ?><?php if($subscription['review_note']): ?><p class="alert alert-light border small mt-3 mb-0"><b>หมายเหตุ:</b> <?= e($subscription['review_note']) ?></p><?php endif ?></div><div class="col-xl-5"><?php if($subscription['subscription_status']==='pending_verification'): ?><form method="post" class="admin-promotion-review"><?= csrf_field() ?><input type="hidden" name="subscription_id" value="<?= (int)$subscription['subscription_id'] ?>"><label class="form-label" for="note-<?= (int)$subscription['subscription_id'] ?>">หมายเหตุ / เหตุผลที่ไม่ผ่าน</label><textarea class="form-control" id="note-<?= (int)$subscription['subscription_id'] ?>" name="review_note" rows="4" maxlength="1000"></textarea><div class="d-grid d-sm-flex gap-2 mt-3"><button class="btn btn-success flex-fill" name="decision" value="approve">อนุมัติและเริ่มแพ็กเกจ</button><button class="btn btn-outline-danger" name="decision" value="reject">ไม่ผ่าน</button></div></form><?php endif ?></div></div></div></article><?php endforeach ?></div><?php else: ?><div class="card border-0"><div class="card-body p-5 text-center text-secondary">ไม่มีรายการในสถานะนี้</div></div><?php endif ?>
</div></main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
