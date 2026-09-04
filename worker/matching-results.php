<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$pdo = db();
$workerId = (int) user()['id'];
$matchedJobs = promotion_attach_to_jobs($pdo, matching_jobs_for_worker($pdo, $workerId, 6));
$matchedCount = count($matchedJobs);

$pageTitle = 'งานที่เหมาะกับคุณ | FLEXJOB';
$pageStyles = ['worker-index', 'matching', 'rating'];
require APP_ROOT . '/partials/header.php';
?>
<main class="worker-home container">
    <section class="worker-home-intro row align-items-center g-4 mb-5" aria-labelledby="matching-results-title">
        <div class="col-lg-8">
            <p class="eyebrow mb-2">YOUR MATCHES ARE READY</p>
            <h1 class="display-6 fw-bold mb-3" id="matching-results-title">นี่คืองานที่<br>เหมาะกับคุณ</h1>
            <p class="lead text-secondary mb-0">เราใช้คำตอบจากแบบสำรวจเพื่อเรียงงานที่ตรงกับความสนใจ ความสามารถ และรูปแบบงานที่คุณเลือกไว้</p>
        </div>
        <div class="col-lg-4">
            <aside class="card border-0 shadow-sm worker-profile-glance" aria-label="สรุปงานที่จับคู่ได้">
                <div class="card-body p-4">
                    <p class="small text-uppercase fw-bold mb-2">MATCHING COMPLETE</p>
                    <div class="d-flex justify-content-between align-items-end gap-3"><strong>งานที่แนะนำ</strong><b><?= $matchedCount ?></b></div>
                    <p class="small text-secondary mb-0 mt-3">กดดูรายละเอียดได้เลย หรือข้ามไปสำรวจงานทั้งหมดก่อนก็ได้</p>
                </div>
            </aside>
        </div>
    </section>

    <section class="worker-recommended" aria-labelledby="matched-jobs-title">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <p class="eyebrow mb-1">JUST FOR YOU</p>
                <h2 class="h3 mb-0" id="matched-jobs-title">งานที่ Matching กับคุณ</h2>
            </div>
            <a class="btn btn-outline-primary px-4" href="<?= BASE_URL ?>/index.php">ข้ามไปหน้าหลัก <span aria-hidden="true">→</span></a>
        </div>

        <?php if ($matchedJobs): ?>
            <div class="row g-4">
                <?php foreach ($matchedJobs as $job) require APP_ROOT . '/partials/worker-job-card.php'; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-5"><div class="fs-2 mb-3" aria-hidden="true">⌕</div><h2 class="h5">ยังไม่มีงานที่เปิดรับตรงกับข้อมูลในตอนนี้</h2><p class="text-secondary mb-4">คุณยังดูงานทั้งหมดได้ และกลับมาแก้แบบสำรวจได้เสมอเมื่อต้องการ</p><a class="btn btn-primary px-4" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด</a></div>
            </div>
        <?php endif ?>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mt-5 pb-5">
            <p class="text-secondary small mb-0">คะแนน Match เป็นคำแนะนำเบื้องต้น คุณเลือกดูและสมัครงานที่สนใจได้ทุกงาน</p>
            <a class="btn btn-link text-decoration-none fw-semibold px-0" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด <span aria-hidden="true">→</span></a>
        </div>
    </section>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
