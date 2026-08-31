<?php
require_once __DIR__ . '/config/config.php';

// Admin ไม่มีหน้า home → redirect ไป dashboard ทันที
if (user() && user()['role'] === 'admin') {
    redirect('admin/dashboard.php');
}

$pdo = db();
$role = user()['role'] ?? '';

// ── ข้อมูลเพิ่มเติมตาม role ──────────────────────────────────────────────────
if ($role === 'worker') {
    $workerId = (int) user()['id'];
    $workerJobs = matching_jobs_for_worker($pdo, $workerId, 6);
    $hasMatchingProfile = (bool) array_filter($workerJobs, fn(array $job): bool => $job['match']['score'] !== null);
    $profileStmt = $pdo->prepare("SELECT wp.professional_headline,wp.biography,wp.resume_file_path,wp.work_province,COUNT(DISTINCT ws.skill_id) skill_count,COUNT(DISTINCT wjp.job_category_id) preference_count,COUNT(DISTINCT wwi.work_interest_id) work_interest_count FROM worker_profiles wp LEFT JOIN worker_skills ws ON ws.worker_user_id=wp.user_id LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=wp.user_id LEFT JOIN worker_work_interests wwi ON wwi.worker_user_id=wp.user_id WHERE wp.user_id=? GROUP BY wp.user_id");
    $profileStmt->execute([$workerId]);
    $matchingProfile = $profileStmt->fetch() ?: [];
    $matchingSurveyComplete = (int) ($matchingProfile['skill_count'] ?? 0) > 0
        && (int) ($matchingProfile['preference_count'] ?? 0) > 0
        && (int) ($matchingProfile['work_interest_count'] ?? 0) > 0
        && ($matchingProfile['work_province'] ?? '') === FLEXJOB_PROVINCE;
    $profileChecks = [
        'คำโปรยแนะนำตัวสั้น ๆ' => !empty($matchingProfile['professional_headline']),
        'ข้อมูลแนะนำตัว'       => !empty($matchingProfile['biography']),
        'ทักษะ'                => (int) ($matchingProfile['skill_count'] ?? 0) > 0,
        'งานที่สนใจ'           => (int) ($matchingProfile['work_interest_count'] ?? 0) > 0,
        'รูปแบบการจ้างที่สนใจ' => (int) ($matchingProfile['preference_count'] ?? 0) > 0,
        'Resume'               => !empty($matchingProfile['resume_file_path']),
    ];
    $profileCompleteness = (int) round(count(array_filter($profileChecks)) * 100 / count($profileChecks));
    $missingProfileItems = array_keys(array_filter($profileChecks, fn(bool $c): bool => !$c));
} elseif ($role === 'employer') {
    $companyStmt = $pdo->prepare("SELECT ep.company_name, COALESCE((SELECT ed.document_status FROM employer_documents ed WHERE ed.employer_user_id=ep.user_id ORDER BY ed.submitted_at DESC LIMIT 1), 'not_submitted') AS verification_status FROM employer_profiles ep WHERE ep.user_id=?");
    $companyStmt->execute([user()['id']]);
    $company = $companyStmt->fetch() ?: ['company_name' => user()['name'], 'verification_status' => 'not_submitted'];
}

// ── Landing page data (ใช้เสมอ) ──────────────────────────────────────────────
$jobStatement = $pdo->prepare("SELECT j.job_id AS id,j.job_title AS title,jc.category_slug AS job_type,wi.interest_name work_interest_name,j.job_description AS description,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,j.open_positions AS positions,ep.company_name,ep.company_logo_path AS company_logo,(SELECT ROUND(AVG(a.rating_by_worker), 1) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) employer_rating_average,(SELECT COUNT(a.rating_by_worker) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) employer_rating_count,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order LIMIT 1) cover_image FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id WHERE j.job_status='published' AND j.work_province=? AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.created_at DESC LIMIT 10");
$jobStatement->execute([FLEXJOB_PROVINCE]);
$jobs = $jobStatement->fetchAll();
$positionStatement = $pdo->prepare("SELECT COALESCE(SUM(open_positions),0) FROM jobs WHERE job_status='published' AND work_province=? AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
$positionStatement->execute([FLEXJOB_PROVINCE]);
$openPositionCount = (int) $positionStatement->fetchColumn();

// เพิ่มรูปโฆษณาชุดใหม่ได้จากรายการนี้ โดยเก็บไฟล์ไว้ใน assets/images
$advertisements = [
    ['image' => 'assets/images/flexjob-advertisement-v1.png', 'alt' => 'FLEXJOB หางานง่าย จบในที่เดียว'],
    ['image' => 'assets/images/flexjob-ad-banner-v1.png', 'alt' => 'FLEXJOB โอกาสงานยืดหยุ่นสำหรับคุณ'],
];

$pageTitle  = 'FLEXJOB | งานที่ยืดหยุ่นสำหรับคุณ';
$pageStyles = match ($role) {
    'worker'   => ['index', 'index-how', 'worker-index', 'worker-profile-guide', 'matching', 'rating'],
    'employer' => ['index', 'index-how', 'employer-index', 'worker-index', 'rating'],
    default    => ['index', 'index-how'],
};
require __DIR__ . '/partials/header.php'; ?>

<main>

    <?php if ($role === 'worker'): ?>
        <!-- ── Worker: ส่วนเพิ่มเติมด้านบน ─────────────────────────────────────────── -->
        <div class="worker-home container">
            <section class="worker-home-intro row align-items-center g-4 mb-4">
                <div class="col-lg-8">
                    <p class="eyebrow mb-2">FLEXJOB FOR YOU</p>
                    <h1 class="display-6 fw-bold mb-3">ค้นหางานที่ใช่<br>ในแบบที่เป็นคุณ</h1>
                    <p class="lead text-secondary mb-4">เติมโปรไฟล์ให้ครบ แล้วเลือกดูงานที่ตรงกับทักษะและรูปแบบงานที่คุณสนใจได้ทันที</p>
                    <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary px-4" href="<?= BASE_URL ?>/jobs.php">ค้นหางาน</a><a class="btn btn-outline-primary px-4" href="<?= BASE_URL ?>/worker/dashboard.php#applications">งานที่สมัคร</a></div>
                </div>
                <div class="col-lg-4">
                    <aside class="card border-0 shadow-sm worker-profile-glance" aria-label="ความพร้อมของโปรไฟล์">
                        <div class="card-body p-4">
                            <p class="small text-uppercase fw-bold mb-2">PROFILE READINESS</p>
                            <div class="d-flex justify-content-between align-items-end gap-3"><strong>โปรไฟล์ของคุณ</strong><b><?= $profileCompleteness ?>%</b></div>
                            <div class="progress mt-3" role="progressbar" aria-label="ความสมบูรณ์ของโปรไฟล์" aria-valuenow="<?= $profileCompleteness ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width:<?= $profileCompleteness ?>%"></div>
                            </div>
                            <p class="small text-secondary mb-0 mt-3">ทักษะ <?= (int) ($matchingProfile['skill_count'] ?? 0) ?> รายการ · งานที่สนใจ <?= (int) ($matchingProfile['work_interest_count'] ?? 0) ?> รายการ</p>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="profile-guide-banner">
                <img src="<?= BASE_URL ?>/assets/images/worker-profile-guide-v2.png" alt="แนะนำการเพิ่ม Resume และ Portfolio ในโปรไฟล์" width="1672" height="941" fetchpriority="high" decoding="async">
                <div class="profile-guide-copy">
                    <p class="eyebrow">COMPLETE YOUR PROFILE</p>
                    <h2>เพิ่มโปรไฟล์ให้โดดเด่น<br>เพื่อให้ผู้ว่าจ้างเห็นคุณมากขึ้น</h2>
                    <p>อัปโหลด Resume และ Portfolio โดยรวม Certificate ไว้ใน Portfolio เพื่อเพิ่มโอกาสได้รับการติดต่อ</p><a class="btn btn-primary btn-sm px-3" href="<?= BASE_URL ?>/worker/editprofiles.php">เติมโปรไฟล์ตอนนี้</a>
                </div>
            </section>

            <?php if (!$matchingSurveyComplete): ?>
                <section class="card border-0 shadow-sm worker-action-card">
                    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                        <div><b>ช่วยให้เราแนะนำงานได้ตรงขึ้น</b>
                            <p class="text-secondary small mb-0 mt-1">ตอบแบบสำรวจ Matching เกี่ยวกับงานที่สนใจ ทักษะ และรูปแบบงาน ใช้เวลาประมาณ 1–2 นาที</p>
                        </div>
                        <a class="btn btn-primary flex-shrink-0" href="<?= BASE_URL ?>/worker/matching-survey.php">เริ่มทำแบบสำรวจ</a>
                    </div>
                </section>
            <?php endif ?>

            <?php if ($profileCompleteness < 100): ?>
                <section class="card border-0 shadow-sm worker-action-card mt-3">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between gap-3 mb-2"><b>ความสมบูรณ์ของโปรไฟล์ <?= $profileCompleteness ?>%</b><span class="text-secondary small"><?= count($missingProfileItems) ?> รายการที่ควรเพิ่ม</span></div>
                                <div class="progress" role="progressbar" aria-label="ความสมบูรณ์ของโปรไฟล์" aria-valuenow="<?= $profileCompleteness ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-primary" style="width:<?= $profileCompleteness ?>%"></div>
                                </div>
                                <p class="text-secondary small mt-2 mb-0">ควรเพิ่ม: <?= e(implode(', ', $missingProfileItems)) ?></p>
                            </div><a class="btn btn-outline-primary align-self-md-center" href="<?= BASE_URL ?>/worker/editprofiles.php">เติมโปรไฟล์</a>
                        </div>
                    </div>
                </section>
            <?php endif ?>

            <section class="worker-recommended mt-5" aria-labelledby="recommended-jobs-title">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                    <div>
                        <p class="eyebrow">JUST FOR YOU</p>
                        <h2 id="recommended-jobs-title"><?= $hasMatchingProfile ? 'งานแนะนำสำหรับคุณ' : 'งานใหม่ล่าสุด' ?></h2><?php if (!$hasMatchingProfile): ?><p class="text-secondary mb-0">เพิ่มทักษะ รูปแบบงาน และประเภทงานที่สนใจเพื่อรับคำแนะนำที่ตรงขึ้น</p><?php endif ?>
                    </div>
                    <a class="btn btn-link text-decoration-none px-0" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด <span aria-hidden="true">→</span></a>
                </div>
                <div class="row g-4"><?php foreach ($workerJobs as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><div class="col-12 col-md-6 col-xl-4"><a class="card h-100 border-0 worker-job-card" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" aria-label="ดูรายละเอียดงาน <?= e($job['title']) ?>"><?php if ($job['cover_image']): ?><img class="card-img-top worker-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>" width="800" height="450" loading="lazy" decoding="async"><?php else: ?><div class="worker-job-image worker-job-fallback" aria-hidden="true"><?= $icon ?></div><?php endif ?><div class="card-body worker-job-body d-flex flex-column">
                                    <h3><?= e($job['title']) ?></h3>
                                    <p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>" width="40" height="40" loading="lazy" decoding="async"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p>
                                    <div class="worker-employer-rating"><?php $ratingSummary = ['average' => $job['employer_rating_average'], 'count' => $job['employer_rating_count']];
                                                                        require APP_ROOT . '/partials/rating-summary.php'; ?></div>
                                    <p class="worker-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p><?php if ($job['match']['score'] !== null): ?><div class="match-summary"><span class="badge text-bg-success"><?= $job['match']['score'] ?>% Match</span><small><?= e($job['match']['reasons'][0] ?? 'ตรงกับข้อมูลที่ระบุ') ?></small></div><?php endif ?><div class="worker-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= e($job['work_interest_name'] ?: job_type($job['job_type'])) ?></span></div>
                                </div></a></div><?php endforeach ?></div>
            </section>

            <section class="worker-latest mt-5" aria-labelledby="latest-jobs-title">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                    <div>
                        <p class="eyebrow">NEWLY POSTED</p>
                        <h2 id="latest-jobs-title">งานที่เพิ่มล่าสุด</h2>
                        <p class="text-secondary mb-0">ดูประกาศงานใหม่จากผู้ว่าจ้างที่ผ่านการยืนยัน</p>
                    </div>
                    <a class="btn btn-link text-decoration-none px-0" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด <span aria-hidden="true">→</span></a>
                </div>
                <div class="row g-4"><?php foreach (array_slice($jobs, 0, 6) as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><div class="col-12 col-md-6 col-xl-4"><a class="card h-100 border-0 worker-job-card" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" aria-label="ดูรายละเอียดงาน <?= e($job['title']) ?>"><?php if ($job['cover_image']): ?><img class="card-img-top worker-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>" width="800" height="450" loading="lazy" decoding="async"><?php else: ?><div class="worker-job-image worker-job-fallback" aria-hidden="true"><?= $icon ?></div><?php endif ?><div class="card-body worker-job-body d-flex flex-column">
                                    <h3><?= e($job['title']) ?></h3>
                                    <p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>" width="40" height="40" loading="lazy" decoding="async"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p>
                                    <div class="worker-employer-rating"><?php $ratingSummary = ['average' => $job['employer_rating_average'], 'count' => $job['employer_rating_count']];
                                                                        require APP_ROOT . '/partials/rating-summary.php'; ?></div>
                                    <p class="worker-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p>
                                    <div class="worker-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= e($job['work_interest_name'] ?: job_type($job['job_type'])) ?></span></div>
                                </div></a></div><?php endforeach ?><?php if (!$jobs): ?><p class="empty">ยังไม่มีงานใหม่ในขณะนี้</p><?php endif ?></div>
            </section>
        </div>

        <section class="how-section" id="how" aria-labelledby="worker-how-title">
            <div class="how-visual" aria-hidden="true">
                <div class="phone">
                    <b>FLEXJOB</b>
                    <h3>สวัสดี 👋</h3>
                    <p>งานใหม่สำหรับคุณ</p>
                    <div>✦ <b>Event Staff</b><small>฿900 / วัน</small></div>
                    <div>⌁ <b>Content Creator</b><small>฿1,500 / งาน</small></div>
                </div>
            </div>
            <div class="how-content">
                <p class="eyebrow">HOW FLEXJOB WORKS</p>
                <h2 id="worker-how-title">หางานง่าย<br>จบในไม่กี่ขั้นตอน</h2>
                <ol>
                    <li><span>01</span>
                        <div><b>สร้างโปรไฟล์ของคุณ</b>
                            <p>เพิ่มข้อมูล ทักษะ และ Resume เพื่อให้ผู้ว่าจ้างรู้จักคุณ</p>
                        </div>
                    </li>
                    <li><span>02</span>
                        <div><b>เลือกงานที่สนใจ</b>
                            <p>ค้นหาและกรองงานตามเวลา จังหวัด และค่าจ้าง</p>
                        </div>
                    </li>
                    <li><span>03</span>
                        <div><b>สมัคร แล้วเริ่มงาน</b>
                            <p>ติดตามผลสมัครและรับข้อเสนอผ่านหน้าเดียว</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>
        <hr class="my-0">

    <?php elseif ($role === 'employer'): ?>
                <?php
        $verificationMeta = [
            'approved'      => ['ยืนยันบัญชีแล้ว', 'success'],
            'pending'       => ['กำลังตรวจสอบบัญชี', 'warning'],
            'rejected'      => ['เอกสารยังไม่ผ่าน', 'danger'],
            'resubmit'      => ['กรุณาส่งเอกสารใหม่', 'danger'],
            'not_submitted' => ['ยังไม่ได้ยืนยันบัญชี', 'secondary'],
        ][$company['verification_status']] ?? ['ยังไม่ได้ยืนยันบัญชี', 'secondary'];
        ?>
        <div class="employer-home container">
            <section class="employer-steps" aria-labelledby="employer-steps-title">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4">
                    <div>
                        <p class="eyebrow mb-2">HOW FLEXJOB WORKS</p>
                        <h2 id="employer-steps-title" class="mb-1">เริ่มจ้างงานได้ใน 3 ขั้นตอน</h2>
                        <p class="text-secondary mb-0">ทุกอย่างที่จำเป็นสำหรับการเริ่มหาคนอยู่ในหน้าเดียว</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4"><article class="card h-100 border-0 shadow-sm employer-step-card"><div class="card-body p-4"><span class="employer-step-number">01</span><h3 class="h5 mt-3">ยืนยันบัญชี</h3><p class="text-secondary small mb-0">ส่งเอกสารเพื่อให้ Admin ตรวจสอบก่อนเริ่มโพสต์งาน</p></div></article></div>
                    <div class="col-md-4"><article class="card h-100 border-0 shadow-sm employer-step-card"><div class="card-body p-4"><span class="employer-step-number">02</span><h3 class="h5 mt-3">สร้างประกาศงาน</h3><p class="text-secondary small mb-0">ระบุรายละเอียด ค่าจ้าง และแนบรูปประกอบให้น่าสนใจ</p></div></article></div>
                    <div class="col-md-4"><article class="card h-100 border-0 shadow-sm employer-step-card"><div class="card-body p-4"><span class="employer-step-number">03</span><h3 class="h5 mt-3">คัดเลือกผู้สมัคร</h3><p class="text-secondary small mb-0">ดูโปรไฟล์ Resume และ Portfolio ก่อนติดต่อผู้สมัครได้โดยตรง</p></div></article></div>
                </div>
            </section>
            <section class="card border-0 shadow-sm overflow-hidden employer-hero" aria-labelledby="employer-home-title">
                <div class="row g-0 align-items-stretch">
                    <div class="col-lg-7">
                        <div class="card-body employer-hero-copy d-flex flex-column justify-content-center h-100">
                            <p class="eyebrow mb-2">EMPLOYER HOME</p>
                            <h1 id="employer-home-title">หาคนที่ใช่<br>ให้งานของคุณ</h1>
                            <p class="lead text-secondary mb-4">สร้างประกาศ ติดตามผู้สมัคร และคัดเลือกคนที่เหมาะกับทีมของคุณได้ในที่เดียว</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-primary px-4" href="<?= BASE_URL ?>/employer/jobpost.php">สร้างประกาศงาน</a>
                                <a class="btn btn-outline-primary px-4" href="<?= BASE_URL ?>/employer/dashboard.php">จัดการประกาศ</a>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-4 small">
                                <span class="fw-semibold"><?= e($company['company_name']) ?></span>
                                <span class="badge rounded-pill text-bg-<?= e($verificationMeta[1]) ?>"><?= e($verificationMeta[0]) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 employer-hero-media">
                        <img src="<?= BASE_URL ?>/assets/images/employer-how-guide-v2.png" alt="ผู้ว่าจ้างกำลังสร้างประกาศงานและคัดเลือกผู้สมัคร" width="1672" height="941" fetchpriority="high" decoding="async">
                    </div>
                </div>
            </section>

            <section class="employer-latest worker-latest mt-5" aria-labelledby="employer-latest-title">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <p class="eyebrow mb-2">NEWLY POSTED</p>
                        <h2 id="employer-latest-title" class="mb-1">งานเพิ่มล่าสุดใน FLEXJOB</h2>
                        <p class="text-secondary mb-0">สำรวจประกาศใหม่จากผู้ว่าจ้างทั้งหมดในระบบ</p>
                    </div>
                    <a class="btn btn-link text-decoration-none px-0 align-self-sm-center" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด <span aria-hidden="true">→</span></a>
                </div>

                                <?php if ($jobs): ?>
                    <div class="row g-4">
                        <?php foreach (array_slice($jobs, 0, 6) as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <a class="card h-100 border-0 worker-job-card" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" aria-label="ดูรายละเอียดงาน <?= e($job['title']) ?>">
                                    <?php if ($job['cover_image']): ?>
                                        <img class="card-img-top worker-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>" width="800" height="450" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div class="worker-job-image worker-job-fallback" aria-hidden="true"><?= $icon ?></div>
                                    <?php endif; ?>
                                    <div class="card-body worker-job-body d-flex flex-column">
                                        <h3><?= e($job['title']) ?></h3>
                                        <p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>" width="40" height="40" loading="lazy" decoding="async"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p>
                                        <div class="worker-employer-rating"><?php $ratingSummary = ['average' => $job['employer_rating_average'], 'count' => $job['employer_rating_count']]; require APP_ROOT . '/partials/rating-summary.php'; ?></div>
                                        <p class="worker-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p>
                                        <div class="worker-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= e($job['work_interest_name'] ?: job_type($job['job_type'])) ?></span></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty text-center mb-0">ยังไม่มีงานใหม่ในขณะนี้</p>
                <?php endif; ?>
            </section>
        </div>
        <hr class="my-0">
    <?php endif; ?>

    <?php if (!$role): ?>
        <!-- ════════════════════════════════════════════════════════════════════════
         LANDING PAGE (ส่วนหลัก — แสดงทุก role)
         ════════════════════════════════════════════════════════════════════════ -->
        <section class="landing-hero py-5">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <p class="eyebrow mb-3">FLEXIBLE WORK, REAL OPPORTUNITY</p>
                        <h1 class="display-4 fw-bold mb-3">งานที่ใช่<br>ในเวลาที่ยืดหยุ่น</h1>
                        <p class="lead mb-4">ค้นหางานพาร์ทไทม์ งานอีเวนต์ และฟรีแลนซ์ในจังหวัด<?= e(FLEXJOB_PROVINCE) ?> จากผู้ว่าจ้างที่ผ่านการตรวจสอบแล้ว</p>
                        <search aria-label="ค้นหางาน">
                            <form class="landing-search rounded-4 p-2" action="<?= BASE_URL ?>/jobs.php" method="get">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5"><label class="visually-hidden" for="landingJobSearch">ค้นหางาน</label><input class="form-control border-0" id="landingJobSearch" type="search" name="q" placeholder="ตำแหน่ง, ทักษะ หรือบริษัท"></div>
                                    <div class="col-md-4"><label class="visually-hidden" for="landingWorkMode">รูปแบบการทำงาน</label><select class="form-select border-0" id="landingWorkMode" name="work_mode">
                                            <option value="">ทุกรูปแบบงาน</option>
                                            <option value="onsite">ทำงานที่สถานที่</option>
                                            <option value="remote">ทำงานออนไลน์</option>
                                            <option value="hybrid">Hybrid</option>
                                        </select></div>
                                    <div class="col-md-3 d-grid"><button class="btn btn-primary fw-semibold" type="submit">ค้นหางาน</button></div>
                                </div>
                            </form>
                        </search>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 landing-popular"><span>ค้นหายอดนิยม</span><a href="<?= BASE_URL ?>/jobs.php?type=event">Staff Event</a><a href="<?= BASE_URL ?>/jobs.php?type=part_time">งานพาร์ทไทม์</a><a href="<?= BASE_URL ?>/jobs.php?type=freelance">กราฟิก</a></div>
                    </div>
                    <div class="col-lg-6">
                        <aside class="hero-visual landing-hero-visual" aria-label="ตัวอย่างการค้นหางานผ่าน FLEXJOB">
                            <div class="float-card top-card"><b>✦ Marketing Crew</b><small>งานอีเวนต์ · 800 บาท/วัน</small></div>
                            <div class="job-board" aria-hidden="true">
                                <div class="job-board-header"><span class="job-board-mark">F</span><b>งานใหม่สำหรับคุณ</b><i></i></div>
                                <div class="job-board-search">⌕ <span>ค้นหางานที่ใช่</span><em>ค้นหา</em></div>
                                <div class="job-board-list">
                                    <div><span class="job-board-icon">✦</span>
                                        <p><b>Event Staff</b><small>900 บาท / วัน</small></p><strong>96%</strong>
                                    </div>
                                    <div><span class="job-board-icon part-time">◷</span>
                                        <p><b>Barista พาร์ทไทม์</b><small>70 บาท / ชม.</small></p><strong>88%</strong>
                                    </div>
                                    <div><span class="job-board-icon freelance">⌁</span>
                                        <p><b>Graphic Designer</b><small>3,500 บาท / โปรเจกต์</small></p><strong>82%</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="float-card bottom-card"><b>✓ โปรไฟล์พร้อมแล้ว</b><small>เริ่มรับงานที่ตรงใจ</small></div>
                            <div class="hero-badge">ตำแหน่งเปิดรับในบุรีรัมย์ <br><strong><?= number_format($openPositionCount) ?></strong> ตำแหน่ง</div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>
        <?php if (!user()): ?>
            <section class="landing-cta container my-5">
                <div class="row align-items-center justify-content-between g-4 rounded-4 p-4 p-lg-5">
                    <div class="col-lg-8">
                        <p class="eyebrow mb-2">START YOUR JOURNEY</p>
                        <h2 class="h1 mb-2">พร้อมเริ่มงานใหม่แล้วหรือยัง?</h2>
                        <p class="mb-0">สร้างโปรไฟล์ อัปโหลด Resume และรับโอกาสงานที่เหมาะกับคุณ</p>
                    </div>
                    <div class="col-lg-auto"><a class="btn btn-light text-primary fw-semibold px-4 py-2" href="<?= BASE_URL ?>/auth/register.php">สร้างโปรไฟล์ฟรี <span aria-hidden="true">→</span></a></div>
                </div>
            </section>
        <?php endif; ?>
        <aside class="ad-banner" aria-label="โฆษณา FLEXJOB">
            <div id="homepageAdvertisementCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover">
                <div class="carousel-indicators">
                    <?php foreach ($advertisements as $index => $advertisement): ?>
                        <button type="button" data-bs-target="#homepageAdvertisementCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?> aria-label="โฆษณาลำดับที่ <?= $index + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($advertisements as $index => $advertisement): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= BASE_URL . '/' . e($advertisement['image']) ?>" alt="<?= e($advertisement['alt']) ?>" <?= $index === 0 ? '' : 'loading="lazy"' ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($advertisements) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#homepageAdvertisementCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">รูปก่อนหน้า</span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#homepageAdvertisementCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">รูปถัดไป</span></button>
                <?php endif; ?>
            </div>
        </aside>
        <section class="landing-explore py-5">
            <div class="container">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <p class="eyebrow mb-2">EXPLORE OPPORTUNITIES</p>
                        <h2 class="h1 mb-0">เลือกงานที่เข้ากับไลฟ์สไตล์คุณ</h2>
                    </div>
                    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด <span aria-hidden="true">→</span></a>
                </div>
                <div class="row g-3" id="jobCategories">
                    <div class="col-6 col-lg-3"><button type="button" class="landing-category-card card h-100 border-0 text-start p-4 shadow-sm active" data-type="all"><i aria-hidden="true">⌘</i><b>งานทั้งหมด</b><small>ทุกโอกาสที่เปิดรับ</small></button></div>
                    <div class="col-6 col-lg-3"><button type="button" class="landing-category-card card h-100 border-0 text-start p-4 shadow-sm" data-type="part_time"><i aria-hidden="true">◷</i><b>พาร์ทไทม์</b><small>ทำงานตามกะ</small></button></div>
                    <div class="col-6 col-lg-3"><button type="button" class="landing-category-card card h-100 border-0 text-start p-4 shadow-sm" data-type="event"><i aria-hidden="true">✦</i><b>งานอีเวนต์</b><small>สนุก ได้ประสบการณ์</small></button></div>
                    <div class="col-6 col-lg-3"><button type="button" class="landing-category-card card h-100 border-0 text-start p-4 shadow-sm" data-type="freelance"><i aria-hidden="true">⌁</i><b>ฟรีแลนซ์</b><small>ทำงานในแบบคุณ</small></button></div>
                </div>
            </div>
        </section>
        <section class="section soft home-recommendations">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">JUST POSTED</p>
                    <h2>งานเปิดรับล่าสุด</h2>
                </div>
                <div class="home-job-controls"><a class="filter-link" href="<?= BASE_URL ?>/jobs.php">☷ ตัวกรอง</a>
                    <form action="<?= BASE_URL ?>/jobs.php" method="get"><select name="sort" onchange="this.form.submit()">
                            <option value="new">ล่าสุด</option>
                            <option value="pay">ค่าจ้างสูงสุด</option>
                        </select></form>
                </div>
            </div>
            <div class="home-jobs-layout">
                <aside class="home-filter-panel"><b>ตัวกรองงาน</b><label>รูปแบบงาน<select id="homeTypeFilter">
                            <option value="all">ทั้งหมด</option>
                            <option value="part_time">พาร์ทไทม์</option>
                            <option value="event">งานอีเวนต์</option>
                            <option value="freelance">ฟรีแลนซ์</option>
                        </select></label><button type="button" id="clearHomeFilters">ล้างตัวกรอง</button></aside>
                <div class="home-job-list" id="homeJobList"><?php foreach ($jobs as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><article class="home-job-row" data-job-type="<?= e($job['job_type']) ?>"><?php if ($job['cover_image']): ?><img class="home-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>"><?php else: ?><div class="home-job-image home-job-icon <?= e($job['job_type']) ?>"><?= $icon ?></div><?php endif ?><div class="home-job-content">
                                <?php if ($job['is_verified']): ?><p class="home-verified">● ผู้ว่าจ้างยืนยันแล้ว</p><?php endif ?>
                                <h3><?= e($job['title']) ?></h3>
                                <p class="home-company"><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?></p>
                                <div class="home-job-tags"><?php if ($job['work_interest_name']): ?><span><?= e($job['work_interest_name']) ?></span><?php endif ?><span><?= job_type($job['job_type']) ?></span><span><?= e($job['positions']) ?> อัตรา</span></div>
                                <p class="home-job-location">⌖ <?= e($job['location']) ?></p>
                                <div class="home-job-footer">
                                    <div><small>◷ <?= e($job['work_date']) ?></small><strong><?= pay_text($job) ?></strong></div><a class="btn btn-primary btn-sm home-job-button" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>">ดูงานนี้</a>
                                </div>
                            </div>
                        </article><?php endforeach ?><p class="empty home-no-results" hidden>ไม่พบงานในหมวดนี้</p><?php if (!$jobs): ?><p class="empty">ยังไม่มีงานแนะนำในขณะนี้</p><?php endif ?></div>
            </div>
        </section>

        <section class="how-section" id="how">
            <div class="how-visual">
                <div class="phone"> <b>FLEXJOB</b>
                    <h3>สวัสดี 👋</h3>
                    <p>งานใหม่สำหรับคุณ</p>
                    <div>✦ <b>Event Staff</b><small>฿900 / วัน</small></div>
                    <div>⌁ <b>Content Creator</b><small>฿1,500 / งาน</small></div>
                </div>
            </div>
            <div class="how-content">
                <p class="eyebrow">HOW FLEXJOB WORKS</p>
                <h2>หางานง่าย<br>จบในไม่กี่ขั้นตอน</h2>
                <ol>
                    <li><span>01</span>
                        <div><b>สร้างโปรไฟล์ของคุณ</b>
                            <p>เพิ่มข้อมูล ทักษะ และ Resume เพื่อให้ผู้ว่าจ้างรู้จักคุณ</p>
                        </div>
                    </li>
                    <li><span>02</span>
                        <div><b>เลือกงานที่สนใจ</b>
                            <p>ค้นหาและกรองงานตามเวลา พื้นที่ และค่าจ้าง</p>
                        </div>
                    </li>
                    <li><span>03</span>
                        <div><b>สมัคร แล้วเริ่มงาน</b>
                            <p>ติดตามผลสมัครและรับข้อเสนอผ่านหน้าเดียว</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

    <?php endif; ?>

</main>
<?php if (!$role): ?>
    <div class="modal fade advertisement-modal" id="advertisementModal" tabindex="-1" aria-labelledby="advertisementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 bg-transparent">
                <h2 class="visually-hidden" id="advertisementModalLabel">โฆษณา FLEXJOB</h2>
                <button class="btn-close btn-close-white advertisement-close" type="button" data-bs-dismiss="modal" aria-label="ปิดโฆษณา"></button>
                <img class="advertisement-image" src="<?= BASE_URL ?>/assets/images/flexjob-ad-banner-v1.png" alt="FLEXJOB หางานง่าย จบในที่เดียว">
                <div class="form-check advertisement-option">
                    <input class="form-check-input" id="hideAdvertisement" type="checkbox">
                    <label class="form-check-label" for="hideAdvertisement">ไม่ต้องแสดงอีก</label>
                </div>
            </div>
        </div>
    </div>
    <script>
        const categoryButtons = document.querySelectorAll('#jobCategories [data-type]');
        const typeSelect = document.querySelector('#homeTypeFilter');
        const jobRows = document.querySelectorAll('#homeJobList .home-job-row');
        const noResults = document.querySelector('.home-no-results');

        function filterHomeJobs(type) {
            let count = 0;
            jobRows.forEach(row => {
                const visible = type === 'all' || row.dataset.jobType === type;
                row.hidden = !visible;
                if (visible) count++;
            });
            noResults.hidden = count !== 0;
            categoryButtons.forEach(button => button.classList.toggle('active', button.dataset.type === type));
            typeSelect.value = type;
            document.querySelector('.home-recommendations').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
        categoryButtons.forEach(button => button.addEventListener('click', () => filterHomeJobs(button.dataset.type)));
        typeSelect.addEventListener('change', () => filterHomeJobs(typeSelect.value));
        document.querySelector('#clearHomeFilters').addEventListener('click', () => filterHomeJobs('all'));

        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.querySelector('#advertisementModal');
            const hideCheckbox = document.querySelector('#hideAdvertisement');

            if (!localStorage.getItem('flexjob-hide-advertisement') && !sessionStorage.getItem('flexjob-advertisement-shown')) {
                window.setTimeout(() => {
                    if (window.scrollY > 240) {
                        new bootstrap.Modal(modalElement).show();
                        sessionStorage.setItem('flexjob-advertisement-shown', '1');
                    }
                }, 12000);
            }

            modalElement.addEventListener('hide.bs.modal', () => {
                if (hideCheckbox.checked) {
                    localStorage.setItem('flexjob-hide-advertisement', '1');
                }
            });
        });
    </script>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>