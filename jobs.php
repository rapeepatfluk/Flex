<?php require_once __DIR__ . '/config/config.php';
require_worker_matching_survey_complete();
promotion_sync_expired(db());
$showAllProvinces = ($_GET['scope'] ?? 'all') !== 'local';
$where = ["j.job_status='published'", "(j.application_deadline IS NULL OR j.application_deadline>=CURDATE())"];
$params = [];
if (!$showAllProvinces) {
    $where[] = 'j.work_province=?';
    $params[] = FLEXJOB_PROVINCE;
}
$jobAreaLabel = $showAllProvinces ? 'ทุกจังหวัด' : FLEXJOB_PROVINCE;
if (!empty($_GET['q'])) {
    $where[] = '(j.job_title LIKE ? OR j.job_description LIKE ? OR ep.company_name LIKE ? OR wi.interest_name LIKE ?)';
    $term = '%' . $_GET['q'] . '%';
    array_push($params, $term, $term, $term, $term);
}
if (!empty($_GET['work_mode']) && in_array($_GET['work_mode'], ['onsite', 'remote', 'hybrid'], true)) {
    $where[] = 'j.work_mode=?';
    $params[] = $_GET['work_mode'];
}
if (!empty($_GET['type']) && in_array($_GET['type'], ['part_time', 'event', 'freelance'], true)) {
    $where[] = 'jc.category_slug=?';
    $params[] = $_GET['type'];
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$countStmt = db()->prepare('SELECT COUNT(*) FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id WHERE ' . implode(' AND ', $where));
$countStmt->execute($params);
$totalJobs = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalJobs / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$stmt = db()->prepare("SELECT j.job_id AS id,j.job_title AS title,jc.category_slug AS job_type,wi.interest_name work_interest_name,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,ep.company_name,ep.company_logo_path AS company_logo,(SELECT ROUND(AVG(a.rating_by_worker), 1) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) employer_rating_average,(SELECT COUNT(a.rating_by_worker) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) employer_rating_count,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order, ji.job_image_id LIMIT 1) AS cover_image,promo.promotion_id,promo.package_code AS promotion_code,promo.display_priority FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id LEFT JOIN (SELECT jp.job_id,jp.promotion_id,jp.starts_at,pp.package_code,pp.display_priority FROM job_promotions jp JOIN promotion_packages pp ON pp.package_id=jp.package_id WHERE jp.promotion_status='active' AND jp.starts_at<=NOW() AND jp.ends_at>NOW()) promo ON promo.job_id=j.job_id WHERE " . implode(' AND ', $where) . " ORDER BY (promo.promotion_id IS NOT NULL) DESC,promo.display_priority DESC,promo.starts_at DESC,j.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$jobs = $stmt->fetchAll();
$pageTitle = 'ค้นหางาน | FLEXJOB';
$pageStyles = ['job-listing', 'rating'];
require APP_ROOT . '/partials/header.php'; ?>
<main class="job-listing">
    <div class="container py-4 py-lg-5">
        <section class="jobs-search-panel">
            <div class="jobs-search-copy">
                <p class="eyebrow">FIND YOUR NEXT JOB</p>
                <h1>ค้นหางานที่ยืดหยุ่น<br>สำหรับคุณ</h1>
                <p>เลือกงานพาร์ทไทม์ งานอีเวนต์ และฟรีแลนซ์ที่เปิดรับใน<?= e($jobAreaLabel) ?></p>
            </div>
            <form class="jobs-search-form row g-2" method="get">
                <?php if ($showAllProvinces): ?><input type="hidden" name="scope" value="all"><?php endif ?>
                <div class="col-12 col-lg-5"><label class="visually-hidden" for="job-search">ชื่อตำแหน่ง หรือบริษัท</label><input id="job-search" class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="ค้นหาตำแหน่ง หรือบริษัท"></div>
                <div class="col-12 col-sm-6 col-lg"><label class="visually-hidden" for="job-work-mode">รูปแบบงาน</label><select id="job-work-mode" class="form-select" name="work_mode"><option value="">ทุกรูปแบบงาน</option><?php foreach (['onsite' => 'ทำงานที่สถานที่', 'remote' => 'ทำงานออนไลน์', 'hybrid' => 'Hybrid'] as $value => $label): ?><option value="<?= $value ?>" <?= ($_GET['work_mode'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="col-12 col-sm-6 col-lg"><label class="visually-hidden" for="job-type">ประเภทงาน</label><select id="job-type" class="form-select" name="type"><option value="">ทุกประเภทงาน</option><?php foreach (['part_time' => 'พาร์ทไทม์', 'event' => 'งานอีเวนต์', 'freelance' => 'ฟรีแลนซ์'] as $value => $label): ?><option value="<?= $value ?>" <?= ($_GET['type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="col-12 col-lg-auto"><button class="btn btn-primary w-100" type="submit">ค้นหางาน</button></div>
            </form>
        </section>

        <section class="jobs-results mt-5">
            <div class="jobs-results-heading">
                <div><p class="eyebrow">AVAILABLE OPPORTUNITIES</p><h2>งานที่เปิดรับสมัคร</h2></div>
                <div class="jobs-result-count"><b><?= number_format($totalJobs) ?></b><span>ตำแหน่งที่ยังเปิดรับใน<?= e($jobAreaLabel) ?></span></div>
            </div>

            <?php if ($jobs): ?>
                <div class="row g-4">
                    <?php foreach ($jobs as $job): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <a class="card h-100 job-card <?= $job['promotion_id'] ? 'is-promoted' : '' ?>" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>">
                                <div class="job-image">
                                    <?php if (!empty($job['cover_image'])): ?>
                                        <img src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>">
                                    <?php else: ?>
                                        <?= $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷') ?>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex flex-wrap gap-2 mb-3"><?php if ($job['promotion_id']): ?><span class="tag promoted-tag">✦ <?= $job['promotion_code'] === 'featured-7d' ? 'ประกาศแนะนำ' : 'โปรโมต' ?></span><?php endif; ?><?php if ($job['work_interest_name']): ?><span class="tag"><?= e($job['work_interest_name']) ?></span><?php endif; ?><span class="tag"><?= job_type($job['job_type']) ?></span></div>
                                    <h3><?= e($job['title']) ?></h3>
                                    <p class="job-company"><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?><?php if ($job['is_verified']): ?><span>✓ ยืนยันแล้ว</span><?php endif; ?></p>
                                    <div class="job-employer-rating"><?php $ratingSummary = ['average' => $job['employer_rating_average'], 'count' => $job['employer_rating_count']]; require APP_ROOT . '/partials/rating-summary.php'; ?></div>
                                    <div class="job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></div>
                                    <div class="job-card-footer mt-auto"><strong><?= pay_text($job) ?></strong><span>ดูรายละเอียด →</span></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="jobs-empty"><span>⌕</span><h3>ไม่พบงานที่ตรงกับเงื่อนไข</h3><p>ลองเปลี่ยนคำค้นหา หรือเลือกประเภทงานอื่นอีกครั้ง</p><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/jobs.php">ล้างตัวกรอง</a></div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?><nav class="jobs-pagination mt-5" aria-label="หน้าผลการค้นหางาน"><ul class="pagination justify-content-center flex-wrap mb-0"><?php for ($number = 1; $number <= $totalPages; $number++): $pageQuery = http_build_query(array_merge($_GET, ['page' => $number])); ?><li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($pageQuery) ?>"><?= $number ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
        </section>
    </div>
</main><?php require APP_ROOT . '/partials/footer.php'; ?>
