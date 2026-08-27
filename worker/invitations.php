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
        if ($status === 'accepted') {
            $openStmt = $pdo->prepare("SELECT 1 FROM job_invitations ji JOIN jobs j ON j.job_id=ji.job_id WHERE ji.job_invitation_id=? AND ji.worker_user_id=? AND j.work_province=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())");
            $openStmt->execute([$invitationId, $workerId, FLEXJOB_PROVINCE]);
            if (!$openStmt->fetchColumn()) throw new RuntimeException('งานนี้ปิดรับสมัครแล้ว');
        }
        $statement = $pdo->prepare("UPDATE job_invitations SET invitation_status=?,responded_at=NOW() WHERE job_invitation_id=? AND worker_user_id=? AND invitation_status IN ('sent','viewed')");
        $statement->execute([$status, $invitationId, $workerId]);
        if (!$statement->rowCount()) throw new RuntimeException('ไม่สามารถอัปเดตคำเชิญได้');
        flash('success', $status === 'accepted' ? 'ตอบรับคำเชิญแล้ว คุณสามารถตรวจสอบและสมัครงานได้' : 'ปฏิเสธคำเชิญแล้ว');
    } catch (Throwable $e) { flash('error', $e->getMessage()); }
    redirect('worker/invitations.php');
}
$pdo->prepare("UPDATE job_invitations SET invitation_status='viewed' WHERE worker_user_id=? AND invitation_status='sent'")->execute([$workerId]);
$statement = $pdo->prepare("SELECT ji.job_invitation_id,ji.invitation_message,ji.invitation_status,ji.created_at,j.job_id,j.job_title,j.job_status,j.application_deadline,j.work_location,j.pay_amount,j.pay_unit,ep.company_name,(j.work_province=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())) is_open FROM job_invitations ji JOIN jobs j ON j.job_id=ji.job_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id WHERE ji.worker_user_id=? ORDER BY ji.created_at DESC");
$statement->execute([FLEXJOB_PROVINCE, $workerId]);
$invitations = $statement->fetchAll();
$pageTitle = 'คำเชิญสมัครงาน | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>
<main class="container py-5"><div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4"><div><p class="eyebrow">JOB INVITATIONS</p><h1 class="h2 mb-1">คำเชิญสมัครงาน</h1><p class="text-secondary mb-0">ตรวจสอบรายละเอียดงานก่อนตัดสินใจสมัครทุกครั้ง</p></div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/jobs.php">ค้นหางาน</a></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4"><?php foreach ($invitations as $invitation): ?><article class="border-bottom py-4"><div class="d-flex flex-column flex-lg-row justify-content-between gap-3"><div><div class="d-flex flex-wrap align-items-center gap-2"><h2 class="h5 mb-0"><?= e($invitation['job_title']) ?></h2><span class="badge text-bg-light border"><?= e(['viewed'=>'เปิดดูแล้ว','accepted'=>'ตอบรับแล้ว','declined'=>'ปฏิเสธแล้ว','sent'=>'ส่งแล้ว'][$invitation['invitation_status']] ?? $invitation['invitation_status']) ?></span><?php if (!$invitation['is_open']): ?><span class="badge text-bg-secondary">ปิดรับแล้ว</span><?php endif ?></div><p class="text-secondary mb-2"><?= e($invitation['company_name']) ?> · <?= pay_text($invitation) ?> · <?= e($invitation['work_location']) ?></p><?php if ($invitation['invitation_message']): ?><blockquote class="mb-0"><?= nl2br(e($invitation['invitation_message'])) ?></blockquote><?php endif ?></div><div class="d-flex flex-wrap gap-2 align-self-lg-center"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/job.php?id=<?= $invitation['job_id'] ?>">ดูรายละเอียดงาน</a><?php if ($invitation['is_open'] && in_array($invitation['invitation_status'], ['sent','viewed'], true)): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="invitation_id" value="<?= $invitation['job_invitation_id'] ?>"><button class="btn btn-sm btn-success" name="status" value="accepted">สนใจงานนี้</button><button class="btn btn-sm btn-outline-secondary" name="status" value="declined">ไม่สนใจ</button></form><?php endif ?></div></div></article><?php endforeach ?><?php if (!$invitations): ?><div class="text-center text-secondary py-5">ยังไม่มีคำเชิญสมัครงาน</div><?php endif ?></div></div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
