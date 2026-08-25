<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$companyStmt = $pdo->prepare("SELECT ep.company_name, COALESCE((SELECT ed.document_status FROM employer_documents ed WHERE ed.employer_user_id=ep.user_id ORDER BY ed.submitted_at DESC LIMIT 1), 'not_submitted') AS verification_status FROM employer_profiles ep WHERE ep.user_id=?");
$companyStmt->execute([user()['id']]);
$company = $companyStmt->fetch() ?: ['company_name' => user()['name'], 'verification_status' => 'not_submitted'];

$jobs = $pdo->query("SELECT j.job_id AS id,j.job_title AS title,jc.category_slug AS job_type,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,ep.company_name,ep.company_logo_path AS company_logo,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order,ji.job_image_id LIMIT 1) cover_image FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id WHERE j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.created_at DESC LIMIT 6")->fetchAll();

$pageTitle = 'Employer Home | FLEXJOB';
$pageStyles = ['employer-index'];
require APP_ROOT . '/partials/header.php';
?>

<main class="employer-home container py-5">
    <section class="employer-guide row align-items-center g-0 overflow-hidden">
        <div class="col-lg-6 order-lg-2"><img src="<?= BASE_URL ?>/assets/images/employer-how-guide-v2.png" alt="ผู้ว่าจ้างกำลังสร้างประกาศงานและคัดเลือกผู้สมัคร"></div>
        <div class="col-lg-6 order-lg-1 employer-guide-copy">
            <p class="eyebrow">EMPLOYER HOME</p>
            <h1>หาคนที่ใช่<br>ให้งานของคุณ</h1>
            <p>ยืนยันบัญชี สร้างประกาศ และจัดการผู้สมัครได้ง่ายในที่เดียว</p>
            <a class="btn btn-success px-4" href="<?= BASE_URL ?>/employer/dashboard.php">จัดการประกาศงาน</a>
        </div>
    </section>

    <section class="employer-steps mt-5" aria-labelledby="employer-steps-title">
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

    <section class="mt-5">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
            <div><p class="eyebrow">EXPLORE FLEXJOB</p><h2>งานที่กำลังเปิดรับ</h2></div>
            <a class="text-link green" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด →</a>
        </div>
        <div class="row g-4"><?php foreach ($jobs as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><div class="col-12 col-md-6 col-xl-4"><a class="card h-100 employer-job-card" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>"><?php if ($job['cover_image']): ?><img class="card-img-top employer-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>"><?php else: ?><div class="employer-job-image employer-job-fallback"><?= $icon ?></div><?php endif ?><div class="card-body d-flex flex-column"><h3><?= e($job['title']) ?></h3><p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p><p class="employer-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p><div class="employer-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= job_type($job['job_type']) ?></span></div></div></a></div><?php endforeach ?></div>
    </section>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
