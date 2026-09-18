<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$employerId = (int) user()['id'];
promotion_sync_expired($pdo);
subscription_sync_statuses($pdo);
$entitlements = subscription_entitlements($pdo, $employerId);

$profileStatement = $pdo->prepare("SELECT u.username,u.first_name,u.last_name,u.email,u.phone,u.created_at,
        ep.company_name,ep.company_description,ep.company_address,ep.company_logo_path,
        COALESCE((SELECT ed.document_status FROM employer_documents ed WHERE ed.employer_user_id=ep.user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1),'not_submitted') verification_status
    FROM users u JOIN employer_profiles ep ON ep.user_id=u.user_id
    WHERE u.user_id=? AND u.role='employer'");
$profileStatement->execute([$employerId]);
$profile = $profileStatement->fetch() ?: [];

$jobStatement = $pdo->prepare("SELECT j.job_id,j.job_title,j.work_location,j.work_schedule,j.application_deadline,j.pay_amount,j.pay_unit,j.created_at,
        j.job_status,CASE WHEN j.job_status='published' AND j.application_deadline IS NOT NULL AND j.application_deadline<CURDATE() THEN 'expired' ELSE j.job_status END display_status,
        (" . application_open_job_sql('j') . ") is_open,
        COUNT(DISTINCT a.application_id) applicant_count,
        COUNT(DISTINCT CASE WHEN a.application_status='completed' THEN a.application_id END) completed_count
    FROM jobs j LEFT JOIN applications a ON a.job_id=j.job_id
    WHERE j.employer_user_id=?
    GROUP BY j.job_id ORDER BY j.created_at DESC,j.job_id DESC");
$jobStatement->execute([$employerId]);
$jobs = $jobStatement->fetchAll();

$totalApplicants = array_sum(array_map(static fn(array $job): int => (int) $job['applicant_count'], $jobs));
$completedHires = array_sum(array_map(static fn(array $job): int => (int) $job['completed_count'], $jobs));
$openJobs = count(array_filter($jobs, static fn(array $job): bool => (bool) $job['is_open']));
$jobFilters = ['all' => ['label' => 'ทั้งหมด'], 'open' => ['label' => 'กำลังเปิดรับ'], 'closed' => ['label' => 'ปิดรับแล้ว']];
$jobFilter = (string) ($_GET['jobs'] ?? 'all');
if (!isset($jobFilters[$jobFilter])) $jobFilter = 'all';
$filteredJobs = match ($jobFilter) {
    'open' => array_values(array_filter($jobs, static fn(array $job): bool => (bool) $job['is_open'])),
    'closed' => array_values(array_filter($jobs, static fn(array $job): bool => !(bool) $job['is_open'])),
    default => $jobs,
};
$jobStatusLabels = ['published' => 'เผยแพร่แล้ว', 'expired' => 'หมดเขตรับสมัคร', 'hidden' => 'ซ่อนประกาศ', 'closed' => 'ปิดรับสมัคร'];

$completedStatement = $pdo->prepare("SELECT a.application_id,a.completed_at,j.job_id,j.job_title,
        CONCAT(u.first_name,' ',u.last_name) worker_name,wp.profile_image_path
    FROM applications a
    JOIN jobs j ON j.job_id=a.job_id
    JOIN users u ON u.user_id=a.worker_user_id
    LEFT JOIN worker_profiles wp ON wp.user_id=a.worker_user_id
    WHERE j.employer_user_id=? AND a.application_status='completed'
    ORDER BY a.completed_at DESC,a.application_id DESC LIMIT 5");
$completedStatement->execute([$employerId]);
$completedApplications = $completedStatement->fetchAll();

$ratingSummary = review_received_summary($pdo, $employerId);
$reviews = review_received_list($pdo, $employerId, 6);
$reportedReviewStatuses = [];
if ($reviews) {
    $reviewIds = array_map(static fn(array $review): int => (int) $review['review_id'], $reviews);
    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    $reportStatement = $pdo->prepare("SELECT review_id,report_status FROM review_reports WHERE reporter_user_id=? AND review_id IN ({$placeholders})");
    $reportStatement->execute(array_merge([$employerId], $reviewIds));
    foreach ($reportStatement->fetchAll() as $reportedReview) $reportedReviewStatuses[(int) $reportedReview['review_id']] = $reportedReview['report_status'];
}

$profileChecks = [
    trim((string) ($profile['company_name'] ?? '')) !== '', trim((string) ($profile['company_description'] ?? '')) !== '',
    trim((string) ($profile['company_address'] ?? '')) !== '', trim((string) ($profile['phone'] ?? '')) !== '',
    !empty($profile['company_logo_path']), ($profile['verification_status'] ?? '') === 'approved',
];
$profileCompletion = (int) round(count(array_filter($profileChecks)) / count($profileChecks) * 100);
$verificationLabels = ['approved' => 'ยืนยันผู้ว่าจ้างแล้ว', 'pending' => 'กำลังตรวจสอบเอกสาร', 'rejected' => 'เอกสารไม่ผ่าน', 'resubmit' => 'กรุณาส่งเอกสารใหม่', 'not_submitted' => 'ยังไม่ได้ยืนยันบัญชี'];
$contactName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$currentSubscription = $entitlements['subscription'];

$pageTitle = 'โปรไฟล์และประวัติผู้ว่าจ้าง | FLEXJOB';
$pageStyles = ['employer-profile'];
require APP_ROOT . '/partials/header.php';
?>

<main class="employer-profile-dashboard py-4 py-lg-5"><div class="container">
    <section class="employer-profile-hero">
        <div class="employer-profile-identity">
            <div class="employer-profile-logo" aria-hidden="true"><?php if (!empty($profile['company_logo_path'])): ?><img src="<?= BASE_URL . '/' . e($profile['company_logo_path']) ?>" alt=""><?php else: ?><?= e(mb_substr($profile['company_name'] ?? 'F', 0, 1)) ?><?php endif ?></div>
            <div class="employer-profile-copy"><p class="employer-eyebrow">EMPLOYER PROFILE</p><div class="employer-title-row"><h1><?= e($profile['company_name'] ?: 'โปรไฟล์ผู้ว่าจ้าง') ?></h1><span class="verification-badge <?= e($profile['verification_status'] ?? 'not_submitted') ?>">✓ <?= e($verificationLabels[$profile['verification_status'] ?? 'not_submitted']) ?></span></div><p><?= e($profile['company_description'] ?: 'เพิ่มรายละเอียดบริษัทเพื่อให้ผู้สมัครรู้จักคุณมากขึ้น') ?></p><div class="employer-profile-meta"><span>⌖ <?= e($profile['company_address'] ?: 'ยังไม่ระบุที่อยู่') ?></span><span>◎ ผู้ติดต่อ <?= e($contactName ?: '-') ?></span></div></div>
        </div>
        <div class="employer-profile-progress-card"><div><span>ความครบถ้วนของโปรไฟล์</span><strong><?= $profileCompletion ?>%</strong></div><div class="employer-profile-progress" role="progressbar" aria-label="ความครบถ้วนของโปรไฟล์" aria-valuenow="<?= $profileCompletion ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= $profileCompletion ?>%"></span></div><p><?= $profileCompletion === 100 ? 'โปรไฟล์บริษัทพร้อมสร้างความน่าเชื่อถือแล้ว' : 'เติมข้อมูลและยืนยันบัญชีเพื่อสร้างความน่าเชื่อถือ' ?></p><a class="btn btn-light text-primary fw-semibold" href="<?= BASE_URL ?>/employer/editprofile.php">แก้ไขข้อมูลบริษัท</a></div>
    </section>

    <section class="employer-profile-stats" aria-label="สรุปข้อมูลผู้ว่าจ้าง">
        <a href="<?= BASE_URL ?>/employer/profile.php?jobs=all#job-history" class="employer-stat <?= $jobFilter === 'all' ? 'is-active' : '' ?>"><span class="employer-stat-icon blue">▣</span><span><small>ประกาศทั้งหมด</small><strong><?= count($jobs) ?></strong><em>รายการ</em></span></a>
        <a href="<?= BASE_URL ?>/employer/profile.php?jobs=open#job-history" class="employer-stat <?= $jobFilter === 'open' ? 'is-active' : '' ?>"><span class="employer-stat-icon green">●</span><span><small>กำลังเปิดรับ</small><strong><?= $openJobs ?></strong><em>ประกาศ</em></span></a>
        <a href="<?= BASE_URL ?>/employer/dashboard.php#all-jobs" class="employer-stat"><span class="employer-stat-icon violet">◎</span><span><small>ผู้สมัครทั้งหมด</small><strong><?= $totalApplicants ?></strong><em>คน</em></span></a>
        <a href="#completed-history" class="employer-stat"><span class="employer-stat-icon amber">✓</span><span><small>จ้างงานสำเร็จ</small><strong><?= $completedHires ?></strong><em>รายการ</em></span></a>
        <a href="#employer-reviews" class="employer-stat"><span class="employer-stat-icon gold">★</span><span><small>รีวิวที่ได้รับ</small><strong><?= (int) ($ratingSummary['count'] ?? 0) ?></strong><em><?= (int) ($ratingSummary['count'] ?? 0) ? number_format((float) $ratingSummary['average'], 1) . '/5' : 'ยังไม่มีคะแนน' ?></em></span></a>
    </section>

    <div class="row g-4 align-items-start"><div class="col-12 col-xl-8">
        <section class="employer-profile-panel" id="job-history" aria-labelledby="job-history-title">
            <div class="employer-panel-header"><div><p class="employer-section-kicker">JOB POST HISTORY</p><h2 id="job-history-title">ประวัติประกาศงาน</h2></div><a href="<?= BASE_URL ?>/employer/jobpost.php">+ สร้างประกาศงาน</a></div>
            <nav class="employer-job-filters" aria-label="กรองประวัติประกาศงาน"><?php foreach ($jobFilters as $filterKey => $filter): ?><a class="<?= $jobFilter === $filterKey ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/employer/profile.php?jobs=<?= e($filterKey) ?>#job-history"><?= e($filter['label']) ?></a><?php endforeach ?></nav>
            <div class="employer-job-list">
                <?php foreach ($filteredJobs as $job): ?><article class="employer-job-item"><span class="employer-job-mark">▣</span><div class="employer-job-main"><div><h3><?= e($job['job_title']) ?></h3><span class="employer-job-status <?= e($job['display_status']) ?>"><?= e($jobStatusLabels[$job['display_status']] ?? $job['display_status']) ?></span></div><p>⌖ <?= e($job['work_location'] ?: 'ไม่ระบุสถานที่') ?> · <?= pay_text($job) ?></p><small>สร้างเมื่อ <?= date('d/m/Y', strtotime($job['created_at'])) ?><?php if ($job['application_deadline']): ?> · ปิดรับ <?= date('d/m/Y', strtotime($job['application_deadline'])) ?><?php endif ?></small></div><div class="employer-job-results"><span><strong><?= (int) $job['applicant_count'] ?></strong> ผู้สมัคร</span><span><strong><?= (int) $job['completed_count'] ?></strong> สำเร็จ</span><a href="<?= BASE_URL ?>/employer/applicants.php?job=<?= (int) $job['job_id'] ?>">ดูผู้สมัคร →</a></div></article><?php endforeach ?>
                <?php if (!$filteredJobs): ?><div class="employer-empty"><span>▣</span><h3>ยังไม่มีประกาศในหมวดนี้</h3><p>สร้างประกาศงานเพื่อเริ่มรับผู้สมัคร</p></div><?php endif ?>
            </div>
        </section>
    </div><div class="col-12 col-xl-4"><div class="employer-side-stack">
        <section class="employer-profile-panel employer-company-info" aria-labelledby="company-info-title"><div class="employer-panel-header compact"><div><p class="employer-section-kicker">COMPANY INFO</p><h2 id="company-info-title">ข้อมูลบัญชี</h2></div><a href="<?= BASE_URL ?>/employer/editprofile.php">แก้ไข</a></div><dl><div><dt>ผู้ติดต่อ</dt><dd><?= e($contactName ?: '-') ?></dd></div><div><dt>อีเมล</dt><dd><?= e($profile['email'] ?? '-') ?></dd></div><div><dt>โทรศัพท์</dt><dd><?= e($profile['phone'] ?: 'ยังไม่ระบุ') ?></dd></div><div><dt>สมาชิกตั้งแต่</dt><dd><?= !empty($profile['created_at']) ? date('d/m/Y', strtotime($profile['created_at'])) : '-' ?></dd></div></dl></section>
        <section class="employer-profile-panel employer-plan-card" aria-labelledby="plan-title"><div class="employer-panel-header compact"><div><p class="employer-section-kicker">CURRENT PLAN</p><h2 id="plan-title">แพ็กเกจ <?= e($entitlements['plan']) ?></h2></div><a href="<?= BASE_URL ?>/employer/subscription.php">จัดการ</a></div><div class="employer-plan-body"><div class="plan-usage"><span>ประกาศที่เปิดอยู่</span><strong><?= $entitlements['open_jobs'] ?> / <?= $entitlements['active_job_limit'] ?></strong></div><div class="plan-bar"><span style="width:<?= min(100, $entitlements['active_job_limit'] > 0 ? round($entitlements['open_jobs'] / $entitlements['active_job_limit'] * 100) : 0) ?>%"></span></div><?php if ($currentSubscription): ?><p>สิ้นสุด <?= date('d/m/Y H:i', strtotime($currentSubscription['ends_at'])) ?></p><small>ใช้สิทธิ์โปรโมตแล้ว <?= $entitlements['promotions_used'] ?>/<?= $entitlements['promotion_credits'] ?> ครั้ง</small><?php else: ?><p>แพ็กเกจ Free ไม่มีวันหมดอายุ</p><small>อัปเกรดเป็น Pro เพื่อเพิ่มจำนวนประกาศและโปรโมตงาน</small><?php endif ?></div></section>
        <section class="employer-profile-panel" id="completed-history" aria-labelledby="completed-title"><div class="employer-panel-header compact"><div><p class="employer-section-kicker">COMPLETED JOBS</p><h2 id="completed-title">การจ้างงานสำเร็จล่าสุด</h2></div></div><div class="completed-list"><?php foreach ($completedApplications as $application): ?><a href="<?= BASE_URL ?>/employer/applicant-detail.php?id=<?= (int) $application['application_id'] ?>&job=<?= (int) $application['job_id'] ?>"><span class="completed-avatar"><?php if ($application['profile_image_path']): ?><img src="<?= BASE_URL . '/' . e($application['profile_image_path']) ?>" alt="" loading="lazy"><?php else: ?><?= e(mb_substr($application['worker_name'], 0, 1)) ?><?php endif ?></span><span><strong><?= e($application['worker_name']) ?></strong><small><?= e($application['job_title']) ?> · <?= $application['completed_at'] ? date('d/m/Y', strtotime($application['completed_at'])) : '-' ?></small></span></a><?php endforeach ?><?php if (!$completedApplications): ?><div class="employer-side-empty">ยังไม่มีประวัติการจ้างงานที่เสร็จสิ้น</div><?php endif ?></div></section>
    </div></div></div>

    <section class="employer-profile-panel employer-review-panel mt-4" id="employer-reviews" aria-labelledby="employer-reviews-title">
        <div class="employer-panel-header"><div><p class="employer-section-kicker">COMPANY REVIEWS</p><h2 id="employer-reviews-title">รีวิวที่ได้รับจากผู้ทำงาน</h2></div><div class="employer-review-score"><span>★</span><?php if ((int) ($ratingSummary['count'] ?? 0)): ?><strong><?= number_format((float) $ratingSummary['average'], 1) ?></strong><small>/ 5 จาก <?= (int) $ratingSummary['count'] ?> รีวิว</small><?php else: ?><strong>–</strong><small>ยังไม่มีรีวิว</small><?php endif ?></div></div>
        <div class="employer-review-grid"><?php foreach ($reviews as $review): ?><?php $reportStatus = $reportedReviewStatuses[(int) $review['review_id']] ?? null; ?><article class="employer-review-card"><div class="employer-review-top"><span class="employer-review-stars" aria-label="<?= (int) $review['rating'] ?> ดาว"><?= str_repeat('★', (int) $review['rating']) ?><i><?= str_repeat('★', 5 - (int) $review['rating']) ?></i></span><time datetime="<?= e(date('Y-m-d', strtotime($review['created_at']))) ?>"><?= date('d/m/Y', strtotime($review['created_at'])) ?></time></div><p>“<?= e($review['review_comment']) ?>”</p><footer><strong><?= e($review['reviewer_name']) ?></strong><span>จากงาน <?= e($review['job_title']) ?></span></footer><div class="employer-review-actions"><?php if ($reportStatus): ?><span class="employer-report-state <?= e($reportStatus) ?>"><?= $reportStatus === 'pending' ? 'ส่งรายงานแล้ว · รอผู้ดูแลตรวจสอบ' : 'ผู้ดูแลตรวจสอบรายงานแล้ว' ?></span><?php else: ?><details class="employer-review-report"><summary>รายงานรีวิว</summary><form method="post" action="<?= BASE_URL ?>/report-review.php"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= (int) $review['review_id'] ?>"><input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/employer/profile.php')) ?>"><label for="employer-report-reason-<?= (int) $review['review_id'] ?>">เหตุผลที่ต้องการรายงาน</label><textarea id="employer-report-reason-<?= (int) $review['review_id'] ?>" class="form-control form-control-sm" name="report_reason" rows="3" maxlength="500" required placeholder="เช่น ใช้ถ้อยคำไม่เหมาะสม หรือข้อมูลไม่เป็นความจริง"></textarea><div><button class="btn btn-sm btn-outline-danger" type="submit">ส่งรายงานให้ผู้ดูแล</button></div></form></details><?php endif ?></div></article><?php endforeach ?><?php if (!$reviews): ?><div class="employer-empty employer-review-empty"><span>☆</span><h3>ยังไม่มีรีวิว</h3><p>รีวิวจากผู้ทำงานจะแสดงหลังงานเสร็จสิ้นและมีการให้คะแนน</p></div><?php endif ?></div>
    </section>
</div></main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
