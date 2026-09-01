<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$pdo = db();
$workerId = (int) user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $invitationId = (int) ($_POST['invitation_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['accepted', 'declined'], true)) throw new RuntimeException('สถานะคำเชิญไม่ถูกต้อง');

        $pdo->beginTransaction();
        $invitationStatement = $pdo->prepare("SELECT ji.job_invitation_id,ji.invitation_status,j.job_id,j.job_title,j.employer_user_id,
            (j.work_province=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())) is_open
            FROM job_invitations ji JOIN jobs j ON j.job_id=ji.job_id
            WHERE ji.job_invitation_id=? AND ji.worker_user_id=? FOR UPDATE");
        $invitationStatement->execute([FLEXJOB_PROVINCE, $invitationId, $workerId]);
        $invitation = $invitationStatement->fetch();
        if (!$invitation || !in_array($invitation['invitation_status'], ['sent', 'viewed'], true)) throw new RuntimeException('ไม่สามารถอัปเดตคำเชิญได้');
        if ($status === 'accepted' && !$invitation['is_open']) throw new RuntimeException('งานนี้ปิดรับสมัครแล้ว');

        $statement = $pdo->prepare('UPDATE job_invitations SET invitation_status=?,responded_at=NOW() WHERE job_invitation_id=?');
        $statement->execute([$status, $invitationId]);
        notification_create(
            $pdo,
            (int) $invitation['employer_user_id'],
            $status === 'accepted' ? 'ผู้หางานตอบรับคำเชิญ' : 'ผู้หางานปฏิเสธคำเชิญ',
            user()['name'] . ($status === 'accepted' ? ' สนใจคำเชิญสมัครงาน: ' : ' ไม่สนใจคำเชิญสมัครงาน: ') . $invitation['job_title'],
            'employer/candidates.php?job=' . $invitation['job_id']
        );
        $pdo->commit();
        flash('success', $status === 'accepted' ? 'ตอบรับคำเชิญแล้ว คุณสามารถตรวจสอบและสมัครงานได้' : 'ปฏิเสธคำเชิญแล้ว');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('worker/invitations.php');
}

$pdo->prepare("UPDATE job_invitations SET invitation_status='viewed' WHERE worker_user_id=? AND invitation_status='sent'")->execute([$workerId]);
$statement = $pdo->prepare("SELECT ji.job_invitation_id,ji.invitation_message,ji.invitation_status,ji.created_at,j.job_id,j.job_title,j.job_status,j.application_deadline,j.work_location,j.pay_amount,j.pay_unit,ep.company_name,ep.company_logo_path,(j.work_province=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())) is_open FROM job_invitations ji JOIN jobs j ON j.job_id=ji.job_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id WHERE ji.worker_user_id=? ORDER BY ji.created_at DESC");
$statement->execute([FLEXJOB_PROVINCE, $workerId]);
$invitations = $statement->fetchAll();

$statusLabels = ['viewed' => 'รอการตอบรับ', 'accepted' => 'ตอบรับแล้ว', 'declined' => 'ปฏิเสธแล้ว', 'sent' => 'คำเชิญใหม่'];
$pendingCount = count(array_filter($invitations, fn(array $invitation): bool => $invitation['is_open'] && in_array($invitation['invitation_status'], ['sent', 'viewed'], true)));
$acceptedCount = count(array_filter($invitations, fn(array $invitation): bool => $invitation['invitation_status'] === 'accepted'));

$pageTitle = 'คำเชิญสมัครงาน | FLEXJOB';
$pageStyles = ['worker-invitations'];
require APP_ROOT . '/partials/header.php';
?>
<main class="worker-invitations-page py-4 py-lg-5">
    <div class="container">
        <section class="invitation-hero card border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5"><div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4"><div><p class="eyebrow mb-2">JOB INVITATIONS</p><h1 class="h2 mb-2">คำเชิญสมัครงาน</h1><p class="mb-0">ผู้ว่าจ้างสนใจโปรไฟล์ของคุณ เลือกดูรายละเอียดก่อนตัดสินใจได้</p></div><a class="btn btn-light text-primary fw-semibold px-4" href="<?= BASE_URL ?>/jobs.php">ค้นหางานทั้งหมด</a></div></div>
        </section>

        <section class="row g-3 mb-4" aria-label="สรุปคำเชิญสมัครงาน">
            <div class="col-12 col-md-4"><article class="card border-0 shadow-sm h-100 invitation-summary"><div class="card-body p-4"><span>คำเชิญทั้งหมด</span><strong><?= count($invitations) ?></strong><small>รายการที่ผู้ว่าจ้างส่งถึงคุณ</small></div></article></div>
            <div class="col-12 col-md-4"><article class="card border-0 shadow-sm h-100 invitation-summary invitation-summary-primary"><div class="card-body p-4"><span>รอการตัดสินใจ</span><strong><?= $pendingCount ?></strong><small>คำเชิญงานที่ยังเปิดรับ</small></div></article></div>
            <div class="col-12 col-md-4"><article class="card border-0 shadow-sm h-100 invitation-summary"><div class="card-body p-4"><span>ตอบรับแล้ว</span><strong><?= $acceptedCount ?></strong><small>สามารถเปิดดูและสมัครงานต่อได้</small></div></article></div>
        </section>

        <section class="card border-0 shadow-sm rounded-4 invitation-list-panel" aria-labelledby="invitation-list-title">
            <div class="card-body p-3 p-md-4 p-lg-5"><div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4"><div><p class="eyebrow mb-1">INVITATION INBOX</p><h2 class="h4 mb-0" id="invitation-list-title">คำเชิญของคุณ</h2></div><span class="badge rounded-pill text-bg-light text-primary border px-3 py-2"><?= count($invitations) ?> รายการ</span></div>
                <div class="vstack gap-3">
                    <?php foreach ($invitations as $invitation): ?>
                        <?php $isPending = $invitation['is_open'] && in_array($invitation['invitation_status'], ['sent', 'viewed'], true); ?>
                        <article class="invitation-card card border <?= $isPending ? 'is-pending' : '' ?>"><div class="card-body p-3 p-md-4"><div class="d-flex flex-column flex-lg-row gap-3 gap-lg-4">
                            <div class="invitation-company-mark flex-shrink-0"><?php if ($invitation['company_logo_path']): ?><img src="<?= BASE_URL . '/' . e($invitation['company_logo_path']) ?>" alt="โลโก้ <?= e($invitation['company_name']) ?>" loading="lazy" decoding="async"><?php else: ?><?= e(mb_substr($invitation['company_name'], 0, 1)) ?><?php endif ?></div>
                            <div class="flex-grow-1 min-w-0"><div class="d-flex flex-wrap align-items-center gap-2 mb-1"><h3 class="h5 mb-0"><?= e($invitation['job_title']) ?></h3><span class="invitation-status <?= e($invitation['invitation_status']) ?>"><?= e($statusLabels[$invitation['invitation_status']] ?? $invitation['invitation_status']) ?></span><?php if (!$invitation['is_open']): ?><span class="invitation-status closed">ปิดรับแล้ว</span><?php endif ?></div><p class="invitation-company mb-2"><?= e($invitation['company_name']) ?></p><div class="d-flex flex-wrap gap-x-3 gap-y-1 invitation-meta"><span>⌖ <?= e($invitation['work_location'] ?: 'ไม่ระบุสถานที่') ?></span><span><?= pay_text($invitation) ?></span><span>ส่งเมื่อ <?= date('d/m/Y', strtotime($invitation['created_at'])) ?></span></div><?php if ($invitation['invitation_message']): ?><div class="invitation-message mt-3"><?= nl2br(e($invitation['invitation_message'])) ?></div><?php endif ?></div>
                            <div class="invitation-actions flex-shrink-0"><a class="btn btn-outline-primary w-100" href="<?= BASE_URL ?>/job.php?id=<?= $invitation['job_id'] ?>">ดูรายละเอียดงาน</a><?php if ($isPending): ?><form action="<?= BASE_URL ?>/worker/invitations.php" method="post" class="d-grid gap-2 mt-2"><?= csrf_field() ?><input type="hidden" name="invitation_id" value="<?= $invitation['job_invitation_id'] ?>"><button class="btn btn-primary" type="submit" name="status" value="accepted">ตอบรับคำเชิญ</button><button class="btn btn-outline-secondary" type="submit" name="status" value="declined">ไม่สนใจ</button></form><?php elseif ($invitation['invitation_status'] === 'accepted' && $invitation['is_open']): ?><a class="btn btn-primary w-100 mt-2" href="<?= BASE_URL ?>/job.php?id=<?= $invitation['job_id'] ?>">ไปสมัครงาน</a><?php endif ?></div>
                        </div></div></article>
                    <?php endforeach ?>
                    <?php if (!$invitations): ?><div class="invitation-empty text-center py-5"><div class="mb-3" aria-hidden="true">✦</div><h3 class="h5">ยังไม่มีคำเชิญสมัครงาน</h3><p class="text-secondary mb-3">เติมข้อมูลโปรไฟล์และเปิดให้ผู้ว่าจ้างค้นพบ เพื่อเพิ่มโอกาสได้รับคำเชิญ</p><a class="btn btn-primary" href="<?= BASE_URL ?>/worker/editprofiles.php">แก้ไขโปรไฟล์</a></div><?php endif ?>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
