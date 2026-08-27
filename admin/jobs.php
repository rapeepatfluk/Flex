<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$pdo = db();

$query = trim($_GET['q'] ?? '');
$sql = 'SELECT j.job_id AS id, j.employer_user_id, j.job_title AS title, j.created_at, jc.category_slug AS job_type, wi.interest_name work_interest_name, ep.company_name FROM jobs j JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id';
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

$pageTitle = 'จัดการประกาศงาน | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow">JOB MODERATION</p>
            <h1 class="h2 mb-1">ตรวจสอบประกาศงานย้อนหลัง</h1>
            <p class="text-secondary mb-0">งานเผยแพร่อัตโนมัติและตรวจสอบภายหลังโดย Admin</p>
        </div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/dashboard.php">กลับหน้า Admin</a>
    </div>
    <form class="row g-2 mb-4" method="get">
        <div class="col-md-6"><input class="form-control" name="q" value="<?= e($query) ?>" placeholder="ค้นหาชื่องาน หรือชื่อบริษัท"></div>
        <div class="col-auto"><button class="btn btn-success" type="submit">ค้นหา</button></div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <?php foreach ($jobs as $job): ?>
                <article class="border-bottom py-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h2 class="h5 mb-1"><?= e($job['title']) ?></h2>
                            <p class="text-secondary small mb-0"><a class="link-primary" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $job['employer_user_id'] ?>"><?= e($job['company_name']) ?></a> · <?= job_type($job['job_type']) ?> · <?= e($job['work_interest_name'] ?: 'ยังไม่เลือกหมวดงาน') ?> · <?= date('d/m/Y', strtotime($job['created_at'])) ?></p>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $job['employer_user_id'] ?>">ผู้ว่าจ้าง</a>
                            <a class="btn btn-sm btn-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>">ดูประกาศ</a>
                            <a class="btn btn-sm btn-danger" href="<?= BASE_URL ?>/admin/jobdelete.php?id=<?= $job['id'] ?>">ลบประกาศ</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$jobs): ?><p class="text-secondary mb-0">ไม่พบประกาศงาน</p><?php endif; ?>
        </div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
