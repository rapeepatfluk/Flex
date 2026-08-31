<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$pdo = db();

$query = trim($_GET['q'] ?? '');
$sql = 'SELECT j.job_id AS id, j.employer_user_id, j.job_title AS title, j.job_status, j.created_at, jc.category_slug AS job_type, wi.interest_name work_interest_name, ep.company_name FROM jobs j JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id';
$params = [];
if ($query !== '') {
    $sql .= ' WHERE j.job_title LIKE ? OR ep.company_name LIKE ?';
    $term = '%' . $query . '%';
    $params = [$term, $term];
}
$sql .= ' ORDER BY j.created_at DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$jobs = $statement->fetchAll();

$statusMeta = [
    'published' => ['label' => 'เผยแพร่แล้ว', 'badge' => 'success', 'icon' => '✓'],
    'hidden' => ['label' => 'ซ่อนประกาศ', 'badge' => 'secondary', 'icon' => '−'],
    'closed' => ['label' => 'ปิดรับสมัคร', 'badge' => 'dark', 'icon' => '■'],
];
$statusCounts = ['published' => 0, 'hidden' => 0, 'closed' => 0];
foreach ($jobs as $job) {
    $status = $job['job_status'] ?? 'published';
    if (array_key_exists($status, $statusCounts)) $statusCounts[$status]++;
}

$pageTitle = 'จัดการประกาศงาน | FLEXJOB';
$pageStyles = ['admin-jobs'];
require APP_ROOT . '/partials/header.php';
?>

<main id="content" class="admin-jobs" tabindex="-1">
    <div class="container">
        <header class="admin-jobs-hero card border-0 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg">
                        <p class="admin-jobs-eyebrow mb-2">JOB MODERATION</p>
                        <h1 class="display-6 mb-2">จัดการประกาศงาน</h1>
                        <p class="admin-jobs-lead mb-0">ตรวจสอบประกาศย้อนหลัง เปิดดูรายละเอียด และดำเนินการลบเมื่อเนื้อหาไม่เหมาะสม</p>
                    </div>
                    <div class="col-lg-auto">
                        <div class="admin-jobs-summary">
                            <span class="admin-jobs-summary-icon" aria-hidden="true">◷</span>
                            <div><strong><?= number_format(count($jobs)) ?></strong><span><?= $query !== '' ? 'ผลลัพธ์จากการค้นหา' : 'ประกาศในรายการ' ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="row g-3 mb-4" aria-label="สรุปสถานะประกาศ">
            <?php foreach ([
                ['published', 'เผยแพร่แล้ว', 'success', '✓'],
                ['hidden', 'ซ่อนประกาศ', 'secondary', '−'],
                ['closed', 'ปิดรับสมัคร', 'dark', '■'],
            ] as [$status, $label, $tone, $icon]): ?>
                <div class="col-6 col-lg-4">
                    <article class="card border-0 admin-jobs-stat admin-jobs-stat-<?= e($tone) ?> h-100">
                        <div class="card-body p-3 p-lg-4">
                            <span class="admin-jobs-stat-icon" aria-hidden="true"><?= e($icon) ?></span>
                            <div><p class="mb-1"><?= e($label) ?></p><strong><?= number_format($statusCounts[$status]) ?></strong></div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="card border-0 admin-jobs-search mb-4" aria-label="ค้นหาประกาศงาน">
            <div class="card-body p-3 p-lg-4">
                <form class="row g-2 align-items-center" method="get">
                    <div class="col-lg">
                        <label class="visually-hidden" for="job-search">ค้นหาชื่องาน หรือชื่อบริษัท</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">⌕</span>
                            <input class="form-control" id="job-search" name="q" value="<?= e($query) ?>" placeholder="ค้นหาชื่องาน หรือชื่อบริษัท">
                        </div>
                    </div>
                    <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit">ค้นหาประกาศ</button></div>
                    <?php if ($query !== ''): ?><div class="col-sm-auto"><a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/admin/jobs.php">ล้างการค้นหา</a></div><?php endif; ?>
                    <div class="col-lg-auto ms-lg-auto"><a class="btn btn-outline-primary w-100" href="<?= BASE_URL ?>/admin/dashboard.php">กลับหน้า Admin</a></div>
                </form>
            </div>
        </section>

        <section aria-labelledby="job-list-heading">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-2 mb-3">
                <div><p class="admin-jobs-eyebrow mb-1">JOB POST LIST</p><h2 class="h3 mb-0" id="job-list-heading"><?= $query !== '' ? 'ผลการค้นหาประกาศ' : 'ประกาศงานทั้งหมด' ?></h2></div>
                <p class="small text-secondary mb-0">แสดง <?= number_format(count($jobs)) ?> ประกาศ</p>
            </div>

            <?php if ($jobs): ?>
                <div class="row g-3">
                    <?php foreach ($jobs as $job):
                        $status = $job['job_status'] ?? 'published';
                        $meta = $statusMeta[$status] ?? ['label' => 'ไม่ทราบสถานะ', 'badge' => 'secondary', 'icon' => '?'];
                    ?>
                        <div class="col-12">
                            <article class="card border-0 admin-job-card">
                                <div class="card-body p-4">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-xl">
                                            <div class="d-flex align-items-start gap-3">
                                                <span class="admin-job-type-icon admin-job-type-<?= e($meta['badge']) ?>" aria-hidden="true"><?= e($meta['icon']) ?></span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                        <span class="badge rounded-pill text-bg-<?= e($meta['badge']) ?>"><?= e($meta['label']) ?></span>
                                                        <span class="admin-job-category"><?= e(job_type($job['job_type'])) ?></span>
                                                    </div>
                                                    <h3 class="h4 mb-1"><?= e($job['title']) ?></h3>
                                                    <p class="mb-3 text-secondary"><a class="link-primary fw-semibold" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $job['employer_user_id'] ?>"><?= e($job['company_name']) ?></a></p>
                                                    <dl class="admin-job-meta mb-0">
                                                        <div><dt>หมวดงาน</dt><dd><?= e($job['work_interest_name'] ?: 'ยังไม่เลือกหมวดงาน') ?></dd></div>
                                                        <div><dt>สร้างประกาศ</dt><dd><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></dd></div>
                                                        <div><dt>รหัสประกาศ</dt><dd>#<?= number_format($job['id']) ?></dd></div>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-auto">
                                            <div class="admin-job-actions">
                                                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $job['employer_user_id'] ?>">ผู้ว่าจ้าง</a>
                                                <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>">ดูประกาศ <span aria-hidden="true">↗</span></a>
                                                <a class="btn btn-danger" href="<?= BASE_URL ?>/admin/jobdelete.php?id=<?= $job['id'] ?>">ลบประกาศ</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 admin-jobs-empty">
                    <div class="card-body text-center p-5">
                        <span class="admin-jobs-empty-icon" aria-hidden="true"><?= $query !== '' ? '⌕' : '◷' ?></span>
                        <h3 class="h4 mt-3"><?= $query !== '' ? 'ไม่พบประกาศที่ตรงกับการค้นหา' : 'ยังไม่มีประกาศงานในระบบ' ?></h3>
                        <p class="text-secondary mb-4"><?= $query !== '' ? 'ลองเปลี่ยนคำค้นหา หรือกลับไปดูประกาศทั้งหมด' : 'เมื่อผู้ว่าจ้างเพิ่มประกาศ รายการจะแสดงที่หน้านี้' ?></p>
                        <?php if ($query !== ''): ?><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/jobs.php">ดูประกาศทั้งหมด</a><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
