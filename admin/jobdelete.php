<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_login('admin');

$pdo = db();
$jobId = (int) ($_GET['id'] ?? $_POST['job_id'] ?? 0);
$jobStmt = $pdo->prepare('SELECT j.job_id, j.job_title, ep.company_name, j.employer_user_id FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id WHERE j.job_id=?');
$jobStmt->execute([$jobId]);
$job = $jobStmt->fetch();

if (!$job) {
    flash('error', 'ไม่พบประกาศงานที่ต้องการลบ');
    redirect('admin/jobs.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') throw new RuntimeException('กรุณาระบุเหตุผลในการลบประกาศ');

        $pdo->prepare('DELETE FROM jobs WHERE job_id=?')->execute([$jobId]);
        admin_notify_user($pdo, (int) $job['employer_user_id'], 'ประกาศงานถูกลบ', 'ประกาศ “' . $job['job_title'] . '” ถูกลบออกจากระบบ — เหตุผล: ' . $reason);
        flash('success', 'ลบประกาศงานแล้วและแจ้งเหตุผลให้ผู้ว่าจ้างแล้ว');
        redirect('admin/jobs.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$pageTitle = 'ยืนยันการลบประกาศ | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <p class="eyebrow">DELETE JOB POST</p>
                    <h1 class="h3 mb-2">ยืนยันการลบประกาศ</h1>
                    <p class="text-secondary">ประกาศ <strong><?= e($job['job_title']) ?></strong> ของ <?= e($job['company_name']) ?> จะถูกลบ พร้อมข้อมูลใบสมัครที่เกี่ยวข้อง</p>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="job_id" value="<?= $jobId ?>"><label class="form-label" for="reason">เหตุผลในการลบ</label><textarea id="reason" class="form-control" name="reason" rows="4" required placeholder="ระบุเหตุผลเพื่อแจ้งผู้ว่าจ้าง"></textarea>
                        <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/jobs.php">ยกเลิก</a><button class="btn btn-danger" type="submit">ยืนยันการลบ</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
