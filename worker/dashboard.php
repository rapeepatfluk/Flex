<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$pdo = db();
$statement = $pdo->prepare("SELECT a.application_id,a.application_status AS status,a.created_at,j.job_title AS title,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,ep.company_name,ep.company_logo_path FROM applications a JOIN jobs j ON j.job_id=a.job_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id WHERE a.worker_user_id=? AND a.application_status <> 'withdrawn' ORDER BY a.created_at DESC");
$statement->execute([user()['id']]);
$applications = $statement->fetchAll();

$statusLabels = [
    'submitted' => 'รอพิจารณา',
    'eligible' => 'มีสิทธิ์สัมภาษณ์',
    'interview_passed' => 'ผ่านสัมภาษณ์แล้ว',
    'completed' => 'งานเสร็จสิ้น',
    'not_selected' => 'ไม่ผ่าน',
    'withdrawn' => 'ถอนใบสมัครแล้ว',
];
$statusCounts = array_count_values(array_column($applications, 'status'));

$pageTitle = 'งานที่สมัครของฉัน | FLEXJOB';
$pageStyles = ['worker-dashboard'];
require APP_ROOT . '/partials/header.php';
?>
<main class="worker-dashboard py-5">
    <div class="container">
        <section class="application-hero card border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                    <div>
                        <p class="eyebrow mb-2">MY APPLICATIONS</p>
                        <h1 class="h2 mb-2">งานที่สมัครของฉัน</h1>
                        <p class="mb-0">ติดตามสถานะการสมัครและดูรายละเอียดงานได้ในที่เดียว</p>
                    </div>
                    <a class="btn btn-light text-primary fw-semibold px-4" href="<?= BASE_URL ?>/jobs.php">ค้นหางานเพิ่ม</a>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4" aria-label="สรุปสถานะการสมัคร">
            <div class="col-12 col-md-6 col-xl-3"><article class="card border-0 shadow-sm h-100 application-summary"><div class="card-body p-4"><span>ใบสมัครทั้งหมด</span><strong><?= count($applications) ?></strong><small>รายการที่คุณติดตามได้</small></div></article></div>
            <div class="col-12 col-md-6 col-xl-3"><article class="card border-0 shadow-sm h-100 application-summary"><div class="card-body p-4"><span>กำลังรอพิจารณา</span><strong><?= (int) ($statusCounts['submitted'] ?? 0) ?></strong><small>ผู้ว่าจ้างกำลังตรวจสอบ</small></div></article></div>
            <div class="col-12 col-md-6 col-xl-3"><article class="card border-0 shadow-sm h-100 application-summary application-summary-highlight"><div class="card-body p-4"><span>มีสิทธิ์สัมภาษณ์</span><strong><?= (int) ($statusCounts['eligible'] ?? 0) ?></strong><small>ตรวจดูข้อมูลติดต่อผู้ว่าจ้าง</small></div></article></div>
            <div class="col-12 col-md-6 col-xl-3"><article class="card border-0 shadow-sm h-100 application-summary"><div class="card-body p-4"><span>งานเสร็จสิ้น</span><strong><?= (int) ($statusCounts['completed'] ?? 0) ?></strong><small>ให้คะแนนผู้ว่าจ้างได้</small></div></article></div>
        </section>

        <section class="card border-0 shadow-sm rounded-4 application-list-panel" id="applications" aria-labelledby="applications-title">
            <div class="card-body p-3 p-md-4 p-lg-5">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-4">
                    <div><p class="eyebrow mb-1">APPLICATION TRACKER</p><h2 class="h4 mb-0" id="applications-title">รายการใบสมัคร</h2></div>
                    <span class="badge rounded-pill text-bg-light text-primary border px-3 py-2"><?= count($applications) ?> รายการ</span>
                </div>

                <div class="vstack gap-3">
                    <?php foreach ($applications as $application): ?>
                        <a class="application-card card border" href="<?= BASE_URL ?>/worker/application-detail.php?id=<?= $application['application_id'] ?>" aria-label="ดูรายละเอียดการสมัคร <?= e($application['title']) ?>">
                            <div class="card-body p-3 p-md-4"><div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                                <div class="application-company-mark flex-shrink-0">
                                    <?php if ($application['company_logo_path']): ?><img src="<?= BASE_URL . '/' . e($application['company_logo_path']) ?>" alt="โลโก้ <?= e($application['company_name']) ?>" loading="lazy" decoding="async"><?php else: ?><?= e(mb_substr($application['company_name'], 0, 1)) ?><?php endif ?>
                                </div>
                                <div class="flex-grow-1 min-w-0"><h3 class="h5 mb-1 text-truncate"><?= e($application['title']) ?></h3><p class="application-company mb-2"><?= e($application['company_name']) ?></p><div class="d-flex flex-wrap gap-x-3 gap-y-1 application-meta"><span>⌖ <?= e($application['location'] ?: 'ไม่ระบุสถานที่') ?></span><span>◷ <?= e($application['work_date'] ?: 'ไม่ระบุเวลา') ?></span><span><?= pay_text($application) ?></span></div></div>
                                <div class="application-card-side flex-shrink-0 text-md-end"><span class="application-status <?= e($application['status']) ?>"><?= e($statusLabels[$application['status']] ?? $application['status']) ?></span><small class="d-block mt-2">สมัครเมื่อ <?= date('d/m/Y', strtotime($application['created_at'])) ?></small><span class="application-detail-link">ดูรายละเอียด <span aria-hidden="true">→</span></span></div>
                            </div></div>
                        </a>
                    <?php endforeach ?>
                    <?php if (!$applications): ?>
                        <div class="application-empty text-center py-5"><div class="mb-3" aria-hidden="true">⌕</div><h3 class="h5">ยังไม่มีงานที่สมัคร</h3><p class="text-secondary mb-3">เริ่มค้นหางานที่เหมาะกับคุณได้เลย</p><a class="btn btn-primary" href="<?= BASE_URL ?>/jobs.php">ค้นหางาน</a></div>
                    <?php endif ?>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>