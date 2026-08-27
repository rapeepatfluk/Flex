<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');
$pdo = db();
$workerId = (int) user()['id'];
$jobs = matching_jobs_for_worker($pdo, $workerId, 6);
$hasMatchingProfile = (bool) array_filter($jobs, fn(array $job): bool => $job['match']['score'] !== null);
$profileStmt = $pdo->prepare("SELECT wp.professional_headline,wp.biography,wp.resume_file_path,wp.work_province,COUNT(DISTINCT ws.skill_id) skill_count,COUNT(DISTINCT wjp.job_category_id) preference_count,COUNT(DISTINCT wwi.work_interest_id) work_interest_count FROM worker_profiles wp LEFT JOIN worker_skills ws ON ws.worker_user_id=wp.user_id LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=wp.user_id LEFT JOIN worker_work_interests wwi ON wwi.worker_user_id=wp.user_id WHERE wp.user_id=? GROUP BY wp.user_id");
$profileStmt->execute([$workerId]);
$matchingProfile = $profileStmt->fetch() ?: [];
$matchingSurveyComplete = (int) ($matchingProfile['skill_count'] ?? 0) > 0
    && (int) ($matchingProfile['preference_count'] ?? 0) > 0
    && (int) ($matchingProfile['work_interest_count'] ?? 0) > 0
    && ($matchingProfile['work_province'] ?? '') === FLEXJOB_PROVINCE;
$profileChecks = [
    'คำโปรยแนะนำตัวสั้น ๆ' => !empty($matchingProfile['professional_headline']),
    'ข้อมูลแนะนำตัว' => !empty($matchingProfile['biography']),
    'ทักษะ' => (int) ($matchingProfile['skill_count'] ?? 0) > 0,
    'งานที่สนใจ' => (int) ($matchingProfile['work_interest_count'] ?? 0) > 0,
    'รูปแบบการจ้างที่สนใจ' => (int) ($matchingProfile['preference_count'] ?? 0) > 0,
    'Resume' => !empty($matchingProfile['resume_file_path']),
];
$profileCompleteness = (int) round(count(array_filter($profileChecks)) * 100 / count($profileChecks));
$missingProfileItems = array_keys(array_filter($profileChecks, fn(bool $complete): bool => !$complete));
$pageTitle = 'งานสำหรับคุณ | FLEXJOB';
$pageStyles = ['worker-index', 'worker-profile-guide', 'worker-how', 'matching'];
require APP_ROOT . '/partials/header.php'; ?>

<main class="worker-home container">
    <section class="profile-guide-banner">
        <img src="<?= BASE_URL ?>/assets/images/worker-profile-guide-v2.png" alt="แนะนำการเพิ่ม Resume และ Portfolio ในโปรไฟล์">
        <div class="profile-guide-copy"><p class="eyebrow">COMPLETE YOUR PROFILE</p><h2>เพิ่มโปรไฟล์ให้โดดเด่น<br>เพื่อให้ผู้ว่าจ้างเห็นคุณมากขึ้น</h2><p>อัปโหลด Resume และ Portfolio โดยรวม Certificate ไว้ใน Portfolio เพื่อเพิ่มโอกาสได้รับการติดต่อ</p></div>
    </section>
    <?php if (!$matchingSurveyComplete): ?><section class="card border-0 shadow-sm mt-4"><div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center"><div><b>ช่วยให้เราแนะนำงานได้ตรงขึ้น</b><p class="text-secondary small mb-0 mt-1">ตอบแบบสำรวจ Matching สั้น ๆ เกี่ยวกับงานที่สนใจ ทักษะ และรูปแบบงาน ใช้เวลาประมาณ 1–2 นาที</p></div><a class="btn btn-success flex-shrink-0" href="<?= BASE_URL ?>/worker/matching-survey.php">เริ่มทำแบบสำรวจ</a></div></section><?php endif ?>
    <?php if ($profileCompleteness < 100): ?><section class="card border-0 shadow-sm mt-4"><div class="card-body p-4"><div class="d-flex flex-column flex-md-row justify-content-between gap-3"><div class="flex-grow-1"><div class="d-flex justify-content-between gap-3 mb-2"><b>ความสมบูรณ์ของโปรไฟล์ <?= $profileCompleteness ?>%</b><span class="text-secondary small"><?= count($missingProfileItems) ?> รายการที่ควรเพิ่ม</span></div><div class="progress" role="progressbar" aria-label="ความสมบูรณ์ของโปรไฟล์" aria-valuenow="<?= $profileCompleteness ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width:<?= $profileCompleteness ?>%"></div></div><p class="text-secondary small mt-2 mb-0">ควรเพิ่ม: <?= e(implode(', ', $missingProfileItems)) ?></p><?php if (empty($matchingProfile['professional_headline'])): ?><p class="text-secondary small mt-1 mb-0">คำโปรยคือประโยคสั้น ๆ ที่บอกความถนัดและงานที่คุณกำลังมองหา เพื่อให้ผู้ว่าจ้างรู้จักคุณได้เร็วขึ้น</p><?php endif ?></div><a class="btn btn-outline-success align-self-md-center" href="<?= BASE_URL ?>/worker/editprofiles.php">เติมโปรไฟล์</a></div></div></section><?php endif ?>
    <div class="worker-welcome d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3">
        <div>
            <p class="eyebrow">WORKER HOME</p>
            <p>ค้นหาโอกาสใหม่ที่เข้ากับเวลาและทักษะของคุณ</p>
        </div><a class="btn btn-success px-4 py-2" href="<?= BASE_URL ?>/jobs.php">ค้นหางานทั้งหมด</a>
    </div>
    <section class="mt-5">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
            <div>
                <h2><?= $hasMatchingProfile ? 'งานแนะนำสำหรับคุณ' : 'งานใหม่ล่าสุด' ?></h2>
                <?php if (!$hasMatchingProfile): ?><p class="text-secondary mb-0">เพิ่มทักษะ รูปแบบงาน และประเภทงานที่สนใจเพื่อรับคำแนะนำที่ตรงขึ้น</p><?php endif ?>
            </div><a class="text-link green" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด →</a>
        </div>
        <div class="row g-4"><?php foreach ($jobs as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><div class="col-12 col-md-6 col-xl-4"><a class="card h-100 worker-job-card" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>"><?php if ($job['cover_image']): ?><img class="card-img-top worker-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>"><?php else: ?><div class="worker-job-image worker-job-fallback"><?= $icon ?></div><?php endif ?><div class="card-body worker-job-body d-flex flex-column">
                        <h3><?= e($job['title']) ?></h3>
                        <p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p>
                        <p class="worker-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p>
                        <?php if ($job['match']['score'] !== null): ?><div class="match-summary"><span class="badge text-bg-success"><?= $job['match']['score'] ?>% Match</span><small><?= e($job['match']['reasons'][0] ?? 'ตรงกับข้อมูลที่ระบุ') ?></small></div><?php endif ?>
                        <div class="worker-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= e($job['work_interest_name'] ?: job_type($job['job_type'])) ?></span></div>
                    </div></a></div><?php endforeach ?></div>
    </section>

    <!-- <section class="worker-how" aria-labelledby="worker-how-title">
        <div class="row align-items-center g-5">
        <div class="col-12 col-md-5">
        <div class="worker-how-visual" aria-hidden="true">
            <div class="worker-how-phone">
                <b>FLEXJOB</b>
                <h3>สวัสดี 👋</h3>
                <p>งานใหม่สำหรับคุณ</p>
                <div><span>✦</span><b>Event Staff</b><small>฿900 / วัน</small></div>
                <div><span>⌁</span><b>Content Creator</b><small>฿1,500 / งาน</small></div>
            </div>
        </div>
        </div>
        <div class="col-12 col-md-7">
        <div class="worker-how-content">
            <p class="eyebrow">HOW FLEXJOB WORKS</p>
            <h2 id="worker-how-title">หางานง่าย<br>จบในไม่กี่ขั้นตอน</h2>
            <ol>
                <li><span>01</span><div><b>สร้างโปรไฟล์ของคุณ</b><p>เพิ่มข้อมูล ทักษะ และ Resume เพื่อให้ผู้ว่าจ้างรู้จักคุณ</p></div></li>
                <li><span>02</span><div><b>เลือกงานที่สนใจ</b><p>ค้นหาและกรองงานตามเวลา พื้นที่ และค่าจ้าง</p></div></li>
                <li><span>03</span><div><b>สมัคร แล้วเริ่มงาน</b><p>ติดตามผลสมัครและรับข้อเสนอผ่านหน้าเดียว</p></div></li>
            </ol>
        </div>
        </div>
        </div>
    </section> -->

</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
