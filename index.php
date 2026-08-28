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
$jobStatement = $pdo->prepare("SELECT j.job_id AS id,j.job_title AS title,jc.category_slug AS job_type,wi.interest_name work_interest_name,j.job_description AS description,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,j.open_positions AS positions,ep.company_name,ep.company_logo_path AS company_logo,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order LIMIT 1) cover_image FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id WHERE j.job_status='published' AND j.work_province=? AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.created_at DESC LIMIT 10");
$jobStatement->execute([FLEXJOB_PROVINCE]);
$jobs = $jobStatement->fetchAll();
$positionStatement = $pdo->prepare("SELECT COALESCE(SUM(open_positions),0) FROM jobs WHERE job_status='published' AND work_province=? AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
$positionStatement->execute([FLEXJOB_PROVINCE]);
$openPositionCount = (int) $positionStatement->fetchColumn();

$pageTitle  = 'FLEXJOB | งานที่ยืดหยุ่นสำหรับคุณ';
$pageStyles = match ($role) {
    'worker'   => ['index', 'index-how', 'worker-index', 'worker-profile-guide', 'matching'],
    'employer' => ['index', 'index-how', 'employer-index'],
    default    => ['index', 'index-how'],
};
require __DIR__ . '/partials/header.php'; ?>

<main>

<?php if ($role === 'worker'): ?>
<!-- ── Worker: ส่วนเพิ่มเติมด้านบน ─────────────────────────────────────────── -->
<div class="worker-home container">
    <section class="profile-guide-banner">
        <img src="<?= BASE_URL ?>/assets/images/worker-profile-guide-v2.png" alt="แนะนำการเพิ่ม Resume และ Portfolio ในโปรไฟล์">
        <div class="profile-guide-copy"><p class="eyebrow">COMPLETE YOUR PROFILE</p><h2>เพิ่มโปรไฟล์ให้โดดเด่น<br>เพื่อให้ผู้ว่าจ้างเห็นคุณมากขึ้น</h2><p>อัปโหลด Resume และ Portfolio โดยรวม Certificate ไว้ใน Portfolio เพื่อเพิ่มโอกาสได้รับการติดต่อ</p></div>
    </section>
    <?php if (!$matchingSurveyComplete): ?><section class="card border-0 shadow-sm mt-4"><div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center"><div><b>ช่วยให้เราแนะนำงานได้ตรงขึ้น</b><p class="text-secondary small mb-0 mt-1">ตอบแบบสำรวจ Matching สั้น ๆ เกี่ยวกับงานที่สนใจ ทักษะ และรูปแบบงาน ใช้เวลาประมาณ 1–2 นาที</p></div><a class="btn btn-success flex-shrink-0" href="<?= BASE_URL ?>/worker/matching-survey.php">เริ่มทำแบบสำรวจ</a></div></section><?php endif ?>
    <?php if ($profileCompleteness < 100): ?><section class="card border-0 shadow-sm mt-4"><div class="card-body p-4"><div class="d-flex flex-column flex-md-row justify-content-between gap-3"><div class="flex-grow-1"><div class="d-flex justify-content-between gap-3 mb-2"><b>ความสมบูรณ์ของโปรไฟล์ <?= $profileCompleteness ?>%</b><span class="text-secondary small"><?= count($missingProfileItems) ?> รายการที่ควรเพิ่ม</span></div><div class="progress" role="progressbar" aria-label="ความสมบูรณ์ของโปรไฟล์" aria-valuenow="<?= $profileCompleteness ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width:<?= $profileCompleteness ?>%"></div></div><p class="text-secondary small mt-2 mb-0">ควรเพิ่ม: <?= e(implode(', ', $missingProfileItems)) ?></p><?php if (empty($matchingProfile['professional_headline'])): ?><p class="text-secondary small mt-1 mb-0">คำโปรยคือประโยคสั้น ๆ ที่บอกความถนัดและงานที่คุณกำลังมองหา เพื่อให้ผู้ว่าจ้างรู้จักคุณได้เร็วขึ้น</p><?php endif ?></div><a class="btn btn-outline-success align-self-md-center" href="<?= BASE_URL ?>/worker/editprofiles.php">เติมโปรไฟล์</a></div></div></section><?php endif ?>
    <section class="mt-4 mb-2">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
            <div>
                <p class="eyebrow">JUST FOR YOU</p>
                <h2><?= $hasMatchingProfile ? 'งานแนะนำสำหรับคุณ' : 'งานใหม่ล่าสุด' ?></h2>
                <?php if (!$hasMatchingProfile): ?><p class="text-secondary mb-0">เพิ่มทักษะ รูปแบบงาน และประเภทงานที่สนใจเพื่อรับคำแนะนำที่ตรงขึ้น</p><?php endif ?>
            </div><a class="text-link green" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด →</a>
        </div>
        <div class="row g-4"><?php foreach ($workerJobs as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><div class="col-12 col-md-6 col-xl-4"><a class="card h-100 worker-job-card" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>"><?php if ($job['cover_image']): ?><img class="card-img-top worker-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>"><?php else: ?><div class="worker-job-image worker-job-fallback"><?= $icon ?></div><?php endif ?><div class="card-body worker-job-body d-flex flex-column"><h3><?= e($job['title']) ?></h3><p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p><p class="worker-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p><?php if ($job['match']['score'] !== null): ?><div class="match-summary"><span class="badge text-bg-success"><?= $job['match']['score'] ?>% Match</span><small><?= e($job['match']['reasons'][0] ?? 'ตรงกับข้อมูลที่ระบุ') ?></small></div><?php endif ?><div class="worker-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= e($job['work_interest_name'] ?: job_type($job['job_type'])) ?></span></div></div></a></div><?php endforeach ?></div>
    </section>
</div>
<hr class="my-0">

<?php elseif ($role === 'employer'): ?>
<!-- ── Employer: ส่วนเพิ่มเติมด้านบน ───────────────────────────────────────── -->
<div class="employer-home container py-4">
    <section class="employer-guide row align-items-center g-0 overflow-hidden">
        <div class="col-lg-6 order-lg-2"><img src="<?= BASE_URL ?>/assets/images/employer-how-guide-v2.png" alt="ผู้ว่าจ้างกำลังสร้างประกาศงานและคัดเลือกผู้สมัคร"></div>
        <div class="col-lg-6 order-lg-1 employer-guide-copy">
            <p class="eyebrow">EMPLOYER HOME</p>
            <h2>หาคนที่ใช่<br>ให้งานของคุณ</h2>
            <p>ยืนยันบัญชี สร้างประกาศ และจัดการผู้สมัครได้ง่ายในที่เดียว</p>
            <a class="btn btn-success px-4" href="<?= BASE_URL ?>/employer/dashboard.php">จัดการประกาศงาน</a>
        </div>
    </section>
    <section class="employer-steps mt-4" aria-labelledby="employer-steps-title">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-2 mb-4">
            <div><p class="eyebrow">HOW FLEXJOB WORKS</p><h2 id="employer-steps-title">เริ่มจ้างงานได้ใน 3 ขั้นตอน</h2></div>
            <a class="text-link green" href="<?= BASE_URL ?>/employer/dashboard.php">ไปยัง Dashboard →</a>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><article class="step-card"><span>01</span><h3>ยืนยันบัญชี</h3><p>ส่งเอกสารเพื่อให้ Admin ตรวจสอบก่อนเริ่มโพสต์งาน</p></article></div>
            <div class="col-md-4"><article class="step-card"><span>02</span><h3>สร้างประกาศงาน</h3><p>ระบุรายละเอียด ค่าจ้าง และแนบรูปประกอบให้น่าสนใจ</p></article></div>
            <div class="col-md-4"><article class="step-card"><span>03</span><h3>คัดเลือกผู้สมัคร</h3><p>ดูโปรไฟล์ Resume และ Portfolio แล้วติดต่อผู้สมัครได้โดยตรง</p></article></div>
        </div>
    </section>
</div>
<hr class="my-0">
<?php endif; ?>

    <!-- ════════════════════════════════════════════════════════════════════════
         LANDING PAGE (ส่วนหลัก — แสดงทุก role)
         ════════════════════════════════════════════════════════════════════════ -->
    <section class="hero">
        <div>
            <p class="eyebrow">FLEXIBLE WORK, REAL OPPORTUNITY</p>
            <h1>งานที่ใช่<br>ในเวลาที่<span>ยืดหยุ่น</span></h1>
            <p class="lead">ค้นหางานพาร์ทไทม์ งานอีเวนต์ และฟรีแลนซ์ในจังหวัด<?= e(FLEXJOB_PROVINCE) ?>จากผู้ว่าจ้างที่ผ่านการตรวจสอบแล้ว</p>
            <form class="search-bar" action="<?= BASE_URL ?>/jobs.php" method="get"><input name="q" placeholder="ค้นหาตำแหน่ง, ทักษะ หรือชื่อบริษัท"><select name="work_mode" aria-label="รูปแบบการทำงาน"><option value="">ทุกรูปแบบงานในบุรีรัมย์</option><option value="onsite">ทำงานที่สถานที่</option><option value="remote">ทำงานออนไลน์</option><option value="hybrid">Hybrid</option></select><button class="btn btn-primary">ค้นหางาน</button></form>
            <p class="popular">ค้นหายอดนิยม: <a href="<?= BASE_URL ?>/jobs.php?type=event">Staff Event</a><a href="<?= BASE_URL ?>/jobs.php?type=part_time">งานพาร์ทไทม์</a><a href="<?= BASE_URL ?>/jobs.php?type=freelance">กราฟิก</a></p>
        </div>
        <div class="hero-visual">
            <div class="float-card top-card"><b>✦ Marketing Crew</b><small>งานอีเวนต์ · 800 บาท/วัน</small></div>
            <div class="hero-person">
                <div class="person-face"></div>
                <div class="person-shirt">FLEXJOB</div>
            </div>
            <div class="float-card bottom-card"><b>✓ ได้งานแล้ว!</b><small>เริ่มงาน 24 ส.ค.</small></div>
            <div class="hero-badge">ตำแหน่งเปิดรับในบุรีรัมย์ <br><strong><?= number_format($openPositionCount) ?></strong> ตำแหน่ง</div>
        </div>
    </section>
    <?php if (!user()): ?>
    <section class="worker-cta">
        <div>
            <p class="eyebrow">START YOUR JOURNEY</p>
            <h2>พร้อมเริ่มงานใหม่แล้วหรือยัง?</h2>
            <p>สร้างโปรไฟล์ อัปโหลด Resume และรับโอกาสงานที่เหมาะกับคุณ</p>
        </div><a class="btn btn-light text-primary fw-semibold px-4 py-2" href="<?= BASE_URL ?>/auth/register.php">สร้างโปรไฟล์ฟรี →</a>
    </section>
    <?php endif; ?>
    <aside class="ad-banner" aria-label="โฆษณา FLEXJOB">
        <img src="<?= BASE_URL ?>/assets/images/flexjob-advertisement-v1.png" alt="FLEXJOB หางานง่าย จบในที่เดียว">
    </aside>
    <section class="section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">EXPLORE OPPORTUNITIES</p>
                <h2>เลือกงานที่เข้ากับไลฟ์สไตล์คุณ</h2>
            </div><a class="text-link green" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด →</a>
        </div>
        <div class="category-grid" id="jobCategories"><button type="button" class="category-card active" data-type="all"><i>⌘</i><b>งานทั้งหมด</b><small>ทุกโอกาสที่เปิดรับ</small></button><button type="button" class="category-card" data-type="part_time"><i>◷</i><b>พาร์ทไทม์</b><small>ทำงานตามกะ</small></button><button type="button" class="category-card" data-type="event"><i>✦</i><b>งานอีเวนต์</b><small>สนุก ได้ประสบการณ์</small></button><button type="button" class="category-card" data-type="freelance"><i>⌁</i><b>ฟรีแลนซ์</b><small>ทำงานในแบบคุณ</small></button></div>
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

</main>
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

        if (!localStorage.getItem('flexjob-hide-advertisement')) {
            new bootstrap.Modal(modalElement).show();
        }

        modalElement.addEventListener('hide.bs.modal', () => {
            if (hideCheckbox.checked) {
                localStorage.setItem('flexjob-hide-advertisement', '1');
            }
        });
    });
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
