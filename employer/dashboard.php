<?php
require_once __DIR__ . '/../config/config.php';

require_login('employer');
$pdo = db();

$profileStmt = $pdo->prepare("SELECT ep.*, COALESCE((SELECT ed.document_status FROM employer_documents ed WHERE ed.employer_user_id=ep.user_id ORDER BY ed.submitted_at DESC LIMIT 1),'not_submitted') AS verification_status FROM employer_profiles ep WHERE ep.user_id=?");
$profileStmt->execute([user()['id']]);
$profile = $profileStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        if (($_POST['action'] ?? '') === 'document') {
            $file = upload_file('criminal_record', ['pdf', 'jpg', 'jpeg', 'png'], 'verification');
            if (!$file) {
                throw new RuntimeException('กรุณาเลือกเอกสาร');
            }

            $pdo->prepare("INSERT INTO employer_documents (employer_user_id,document_file_path,document_status) VALUES (?,?,'pending')")
                ->execute([user()['id'], $file]);
            flash('success', 'ส่งเอกสารแล้ว กรุณารอ Admin ตรวจสอบ');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('employer/dashboard.php');
}

$jobsStmt = $pdo->prepare("SELECT
    j.job_id AS id,
    j.job_title AS title,
    jc.category_slug AS job_type,
    wi.interest_name AS work_interest_name,
    j.work_location,
    j.work_schedule,
    j.pay_amount,
    j.pay_unit,
    j.open_positions,
    j.job_status AS status,
    j.created_at,
    COUNT(DISTINCT a.application_id) AS applicants,
    COUNT(DISTINCT js.skill_id) AS matching_skills,
    (SELECT ji.image_file_path
     FROM job_images ji
     WHERE ji.job_id=j.job_id
     ORDER BY ji.display_order, ji.job_image_id
     LIMIT 1) AS cover_image
    FROM jobs j
    JOIN job_categories jc ON jc.job_category_id=j.job_category_id
    LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id
    LEFT JOIN applications a ON a.job_id=j.job_id
    LEFT JOIN job_skills js ON js.job_id=j.job_id
    WHERE j.employer_user_id=?
    GROUP BY j.job_id
    ORDER BY j.created_at DESC");
$jobsStmt->execute([user()['id']]);
$jobs = $jobsStmt->fetchAll();

$publishedJobs = count(array_filter($jobs, fn(array $job): bool => $job['status'] === 'published'));
$totalApplicants = array_sum(array_map(fn(array $job): int => (int) $job['applicants'], $jobs));
$matchingReadyJobs = count(array_filter($jobs, fn(array $job): bool => (int) $job['matching_skills'] > 0));
$latestJobs = array_slice($jobs, 0, 3);

$verificationMeta = [
    'approved' => ['ยืนยันบัญชีแล้ว', 'success', 'บัญชีของคุณพร้อมสร้างประกาศและคัดเลือกผู้สมัคร'],
    'pending' => ['กำลังตรวจสอบเอกสาร', 'warning', 'เราจะแจ้งผลหลังผู้ดูแลตรวจเอกสารเรียบร้อย'],
    'rejected' => ['เอกสารยังไม่ผ่าน', 'danger', 'ตรวจสอบเอกสารและส่งใหม่ได้จากด้านล่าง'],
    'resubmit' => ['กรุณาส่งเอกสารใหม่', 'danger', 'กรุณาส่งเอกสารที่ถูกต้องเพื่อยืนยันบัญชี'],
    'not_submitted' => ['ยังไม่ได้ยืนยันบัญชี', 'secondary', 'ยืนยันบัญชีเพื่อเพิ่มความน่าเชื่อถือให้ผู้สมัคร'],
][$profile['verification_status']] ?? ['ยังไม่ได้ยืนยันบัญชี', 'secondary', 'ยืนยันบัญชีเพื่อเพิ่มความน่าเชื่อถือให้ผู้สมัคร'];

$jobStatusMeta = [
    'published' => ['เผยแพร่แล้ว', 'success'],
    'hidden' => ['ซ่อนประกาศ', 'secondary'],
    'closed' => ['ปิดรับสมัคร', 'dark'],
];

$pageTitle = 'Dashboard ผู้ว่าจ้าง | FLEXJOB';
$pageStyles = ['employer-dashboard'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container employer-dashboard-page py-4 py-lg-5">
    <section class="card border-0 shadow-sm employer-dashboard-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="employer-dashboard-eyebrow mb-2">EMPLOYER DASHBOARD</p>
                    <h1 class="display-6 fw-bold mb-2"><?= e($profile['company_name']) ?></h1>
                    <p class="lead mb-0 text-secondary">จัดการประกาศงาน ติดตามผู้สมัคร และค้นหาคนที่เหมาะกับทีมของคุณได้ในที่เดียว</p>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2 justify-content-lg-end">
                        <a class="btn btn-primary btn-lg px-4" href="<?= BASE_URL ?>/employer/jobpost.php">สร้างประกาศงาน</a>
                        <a class="btn btn-outline-primary btn-lg px-4" href="<?= BASE_URL ?>/employer/candidates.php">ค้นหาผู้หางาน</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 g-lg-4 mb-4" aria-label="สรุปประกาศงาน">
        <div class="col-md-4">
            <article class="card border-0 shadow-sm h-100 employer-summary-card">
                <div class="card-body p-4">
                    <span class="employer-summary-icon text-primary">▣</span>
                    <p class="text-secondary mb-1">ประกาศทั้งหมด</p>
                    <strong class="display-6 fw-bold"><?= count($jobs) ?></strong>
                    <small class="d-block text-secondary mt-2">เผยแพร่อยู่ <?= $publishedJobs ?> ประกาศ</small>
                </div>
            </article>
        </div>
        <div class="col-md-4">
            <article class="card border-0 shadow-sm h-100 employer-summary-card">
                <div class="card-body p-4">
                    <span class="employer-summary-icon text-info">◎</span>
                    <p class="text-secondary mb-1">ผู้สมัครทั้งหมด</p>
                    <strong class="display-6 fw-bold"><?= $totalApplicants ?></strong>
                    <small class="d-block text-secondary mt-2">จากทุกประกาศงานของคุณ</small>
                </div>
            </article>
        </div>
        <div class="col-md-4">
            <article class="card border-0 shadow-sm h-100 employer-summary-card employer-summary-card-highlight">
                <div class="card-body p-4">
                    <span class="employer-summary-icon text-primary">✦</span>
                    <p class="text-secondary mb-1">พร้อมใช้ Matching</p>
                    <strong class="display-6 fw-bold"><?= $matchingReadyJobs ?></strong>
                    <small class="d-block text-secondary mt-2">ประกาศที่ระบุทักษะไว้แล้ว</small>
                </div>
            </article>
        </div>
    </section>

    <section class="card border-0 shadow-sm employer-verification-card mb-5">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg">
                    <div class="d-flex align-items-start gap-3">
                        <span class="employer-verification-icon bg-<?= e($verificationMeta[1]) ?>-subtle text-<?= e($verificationMeta[1]) ?>">✓</span>
                        <div>
                            <p class="employer-dashboard-eyebrow mb-1">ACCOUNT VERIFICATION</p>
                            <h2 class="h5 mb-1">สถานะบัญชี: <?= e($verificationMeta[0]) ?></h2>
                            <p class="mb-0 text-secondary"><?= e($verificationMeta[2]) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-auto">
                    <?php if ($profile['verification_status'] === 'approved'): ?>
                        <span class="badge rounded-pill text-bg-success px-3 py-2">ยืนยันผู้ว่าจ้างแล้ว</span>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="document">
                            <div class="col-12 col-sm">
                                <label class="visually-hidden" for="criminalRecord">เอกสารประวัติอาชญากรรม</label>
                                <input class="form-control" id="criminalRecord" type="file" name="criminal_record" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary text-nowrap" type="submit">ส่งเอกสาร</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5" aria-labelledby="latestJobsHeading">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <p class="employer-dashboard-eyebrow mb-1">RECENT JOB POSTS</p>
                <h2 class="h3 mb-0" id="latestJobsHeading">ประกาศล่าสุดของคุณ</h2>
            </div>
            <?php if ($jobs): ?>
                <a class="link-primary link-offset-2 text-decoration-none fw-semibold" href="#all-jobs">จัดการประกาศทั้งหมด →</a>
            <?php endif; ?>
        </div>

        <?php if ($latestJobs): ?>
            <div class="row g-4">
                <?php foreach ($latestJobs as $job):
                    $status = $jobStatusMeta[$job['status']] ?? ['ไม่ทราบสถานะ', 'secondary'];
                ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="card border-0 shadow-sm h-100 employer-latest-job">
                            <?php if ($job['cover_image']): ?>
                                <img class="card-img-top employer-latest-image" src="<?= BASE_URL ?>/<?= e($job['cover_image']) ?>" alt="" width="800" height="450" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="employer-latest-fallback" aria-hidden="true"><span>F</span></div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <span class="badge rounded-pill text-bg-<?= e($status[1]) ?>"><?= e($status[0]) ?></span>
                                    <small class="text-secondary text-nowrap"><?= date('d/m/Y', strtotime($job['created_at'])) ?></small>
                                </div>
                                <h3 class="h5 mb-2"><?= e($job['title']) ?></h3>
                                <p class="small text-secondary mb-3"><?= e($job['work_location'] ?: 'ยังไม่ได้ระบุสถานที่') ?> · <?= e($job['work_schedule'] ?: 'ยังไม่ได้ระบุเวลา') ?></p>
                                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
                                    <strong class="text-primary"><?= pay_text($job) ?></strong>
                                    <span class="small text-secondary"><?= (int) $job['applicants'] ?> ผู้สมัคร · <?= (int) $job['open_positions'] ?> อัตรา</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <a class="btn btn-primary btn-sm flex-grow-1" href="<?= BASE_URL ?>/employer/applicants.php?job=<?= $job['id'] ?>">ดูผู้สมัคร</a>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/employer/candidates.php?job=<?= $job['id'] ?>" aria-label="ค้นหาคนที่เหมาะกับ <?= e($job['title']) ?>">ค้นหาคน</a>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <article class="card border-0 shadow-sm employer-empty-state">
                <div class="card-body text-center p-5">
                    <span class="employer-empty-icon" aria-hidden="true">＋</span>
                    <h3 class="h4 mt-3">เริ่มสร้างประกาศงานแรกของคุณ</h3>
                    <p class="text-secondary mb-4">เพิ่มรายละเอียดงานและทักษะที่ต้องการ เพื่อให้ FLEXJOB ช่วยจับคู่ผู้สมัครได้แม่นยำขึ้น</p>
                    <a class="btn btn-primary" href="<?= BASE_URL ?>/employer/jobpost.php">สร้างประกาศงาน</a>
                </div>
            </article>
        <?php endif; ?>
    </section>

        <?php if ($jobs): ?>
        <section class="card border-0 shadow-sm employer-job-manager" id="all-jobs" aria-labelledby="allJobsHeading">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <p class="employer-dashboard-eyebrow mb-1">JOB MANAGEMENT</p>
                        <h2 class="h3 mb-0" id="allJobsHeading">จัดการประกาศทั้งหมด</h2>
                    </div>
                    <span class="badge rounded-pill text-bg-primary px-3 py-2"><?= count($jobs) ?> ประกาศ</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="vstack gap-3">
                    <?php foreach ($jobs as $job):
                        $status = $jobStatusMeta[$job['status']] ?? ['ไม่ทราบสถานะ', 'secondary'];
                    ?>
                        <article class="employer-job-row p-3 p-lg-4">
                            <div class="row align-items-center g-3">
                                <div class="col-lg">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <h3 class="h5 mb-0"><?= e($job['title']) ?></h3>
                                        <span class="badge rounded-pill text-bg-<?= e($status[1]) ?>"><?= e($status[0]) ?></span>
                                    </div>
                                    <p class="mb-1 text-secondary"><?= job_type($job['job_type']) ?> · <?= e($job['work_interest_name'] ?: 'ยังไม่ได้เลือกหมวดงาน') ?></p>
                                    <p class="small text-secondary mb-0"><?= e($job['work_location'] ?: 'ยังไม่ได้ระบุสถานที่') ?> · <?= e($job['work_schedule'] ?: 'ยังไม่ได้ระบุเวลา') ?> · <?= pay_text($job) ?></p>
                                    <?php if (!$job['work_interest_name']): ?>
                                        <p class="small text-warning-emphasis mb-0 mt-2">ควรเลือกหมวดงาน เพื่อให้ Matching แม่นยำขึ้น</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-auto">
                                    <div class="d-flex gap-3 text-nowrap employer-job-metrics">
                                        <span><strong><?= (int) $job['applicants'] ?></strong><small>ผู้สมัคร</small></span>
                                        <span><strong><?= (int) $job['matching_skills'] ?></strong><small>ทักษะ</small></span>
                                    </div>
                                </div>
                                <div class="col-lg-auto">
                                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/employer/applicants.php?job=<?= $job['id'] ?>">ผู้สมัคร</a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/employer/candidates.php?job=<?= $job['id'] ?>">ค้นหาคน</a>
                                        <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/employer/jobedit.php?id=<?= $job['id'] ?>">แก้ไข</a>
                                        <form method="post" action="<?= BASE_URL ?>/employer/jobdelete.php" onsubmit="return confirm('ยืนยันการลบประกาศงานนี้? ข้อมูลผู้สมัครของประกาศนี้จะถูกลบด้วย');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
