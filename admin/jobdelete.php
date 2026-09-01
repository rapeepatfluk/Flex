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

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM jobs WHERE job_id=?')->execute([$jobId]);
        admin_notify_user($pdo, (int) $job['employer_user_id'], 'ประกาศงานถูกลบ', 'ประกาศ “' . $job['job_title'] . '” ถูกลบออกจากระบบ — เหตุผล: ' . $reason);
        $pdo->commit();
        flash('success', 'ลบประกาศงานแล้วและแจ้งเหตุผลให้ผู้ว่าจ้างแล้ว');
        redirect('admin/jobs.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
}

$pageTitle = 'ยืนยันการลบประกาศ | FLEXJOB';
$pageStyles = ['admin-jobdelete'];
require APP_ROOT . '/partials/header.php';
?>

<main id="content" class="admin-jobdelete" tabindex="-1">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <a class="admin-jobdelete-back" href="<?= BASE_URL ?>/admin/jobs.php"><span aria-hidden="true">←</span> กลับไปจัดการประกาศ</a>

                <section class="card border-0 admin-jobdelete-card" aria-labelledby="delete-job-heading">
                    <div class="card-body p-4 p-lg-5">
                        <header class="admin-jobdelete-heading">
                            <span class="admin-jobdelete-icon" aria-hidden="true">!</span>
                            <div>
                                <p class="admin-jobdelete-eyebrow mb-1">DELETE JOB POST</p>
                                <h1 class="h2 mb-2" id="delete-job-heading">ยืนยันการลบประกาศ</h1>
                                <p class="text-secondary mb-0">การดำเนินการนี้ไม่สามารถย้อนกลับได้ โปรดตรวจสอบรายละเอียดก่อนยืนยัน</p>
                            </div>
                        </header>

                        <section class="admin-jobdelete-target" aria-labelledby="job-to-delete-heading">
                            <p class="admin-jobdelete-target-label mb-2">ประกาศที่กำลังจะลบ</p>
                            <h2 class="h4 mb-1" id="job-to-delete-heading"><?= e($job['job_title']) ?></h2>
                            <p class="mb-0"><?= e($job['company_name']) ?> <span aria-hidden="true">·</span> รหัสประกาศ #<?= number_format($jobId) ?></p>
                        </section>

                        <aside class="admin-jobdelete-warning" role="alert">
                            <span aria-hidden="true">!</span>
                            <div><strong>ข้อมูลใบสมัครที่เกี่ยวข้องจะถูกลบออกด้วย</strong><p class="mb-0">ระบบจะส่งเหตุผลที่ระบุให้ผู้ว่าจ้างทราบทันทีหลังยืนยันการลบ</p></div>
                        </aside>

                        <form method="post" action="<?= BASE_URL ?>/admin/jobdelete.php?id=<?= $jobId ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="job_id" value="<?= $jobId ?>">
                            <fieldset class="admin-jobdelete-form">
                                <legend>เหตุผลในการลบ <span class="text-danger">*</span></legend>
                                <p id="reason-help">ข้อความนี้จะถูกใช้แจ้งให้ผู้ว่าจ้างทราบ</p>
                                <label class="visually-hidden" for="reason">ระบุเหตุผลในการลบประกาศ</label>
                                <textarea id="reason" class="form-control" name="reason" rows="4" required aria-describedby="reason-help" placeholder="เช่น เนื้อหาประกาศไม่เป็นไปตามนโยบายการใช้งาน"></textarea>
                            </fieldset>
                            <div class="admin-jobdelete-actions">
                                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/jobs.php">ยกเลิก</a>
                                <button class="btn btn-danger" type="submit">ยืนยันการลบประกาศ</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
