<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$pdo = db();

if (!function_exists('report_date')) {
    function report_date(string $value, string $fallback): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }
}

$defaultTo = date('Y-m-d');
$defaultFrom = date('Y-m-d', strtotime('-29 days'));
$from = report_date((string) ($_GET['from'] ?? ''), $defaultFrom);
$to = report_date((string) ($_GET['to'] ?? ''), $defaultTo);
if ($from > $to) [$from, $to] = [$to, $from];
$printView = (string) ($_GET['print'] ?? '') === '1';
$rangeParams = [$from, $to];

$count = static function (string $sql, array $params = []) use ($pdo): int {
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
};

$stats = [
    'employers' => $count("SELECT COUNT(*) FROM users WHERE role='employer' AND created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
    'workers' => $count("SELECT COUNT(*) FROM users WHERE role='worker' AND created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
    'jobs' => $count("SELECT COUNT(*) FROM jobs WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
    'applications' => $count("SELECT COUNT(*) FROM applications WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
    'completed' => $count("SELECT COUNT(*) FROM applications WHERE application_status='completed' AND completed_at>=? AND completed_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
    'pro_approved' => $count("SELECT COUNT(*) FROM employer_subscriptions WHERE subscription_status IN ('active','expired') AND reviewed_at>=? AND reviewed_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
    'pro_promotions' => $count("SELECT COUNT(*) FROM job_promotions WHERE starts_at>=? AND starts_at<DATE_ADD(?,INTERVAL 1 DAY)", $rangeParams),
];

$revenueStatement = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM employer_subscriptions
    WHERE subscription_status IN ('active','expired') AND reviewed_at>=? AND reviewed_at<DATE_ADD(?,INTERVAL 1 DAY)");
$revenueStatement->execute($rangeParams);
$stats['revenue'] = (float) $revenueStatement->fetchColumn();

$categoryStatement = $pdo->prepare("SELECT jc.category_slug,
    COUNT(DISTINCT CASE WHEN j.created_at>=? AND j.created_at<DATE_ADD(?,INTERVAL 1 DAY) THEN j.job_id END) AS job_count,
    COUNT(DISTINCT CASE WHEN a.created_at>=? AND a.created_at<DATE_ADD(?,INTERVAL 1 DAY) THEN a.application_id END) AS application_count
    FROM job_categories jc
    LEFT JOIN jobs j ON j.job_category_id=jc.job_category_id
    LEFT JOIN applications a ON a.job_id=j.job_id
    GROUP BY jc.job_category_id,jc.category_slug
    HAVING job_count>0 OR application_count>0
    ORDER BY job_count DESC,application_count DESC,jc.category_slug ASC");
$categoryStatement->execute([$from, $to, $from, $to]);
$categories = $categoryStatement->fetchAll();

$statusStatement = $pdo->prepare("SELECT job_status,COUNT(*) AS job_count
    FROM jobs
    WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)
    GROUP BY job_status
    ORDER BY job_count DESC,job_status ASC");
$statusStatement->execute($rangeParams);
$jobStatuses = $statusStatement->fetchAll();

$subscriptionStatusStatement = $pdo->prepare("SELECT subscription_status,COUNT(*) AS subscription_count
    FROM employer_subscriptions
    WHERE created_at>=? AND created_at<DATE_ADD(?,INTERVAL 1 DAY)
    GROUP BY subscription_status
    ORDER BY subscription_count DESC,subscription_status ASC");
$subscriptionStatusStatement->execute($rangeParams);
$subscriptionStatuses = $subscriptionStatusStatement->fetchAll();

$statusLabels = [
    'published' => 'เผยแพร่แล้ว',
    'hidden' => 'ซ่อนประกาศ',
    'closed' => 'ปิดรับสมัคร',
];
$subscriptionStatusLabels = [
    'pending_payment' => 'รอชำระเงิน',
    'pending_verification' => 'รอตรวจสลิป',
    'active' => 'กำลังใช้งาน',
    'rejected' => 'ชำระเงินไม่ผ่าน',
    'expired' => 'หมดอายุ',
    'cancelled' => 'ยกเลิก',
];

if ((string) ($_GET['export'] ?? '') === 'csv') {
    $filename = 'flexjob-report-' . $from . '-to-' . $to . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['รายงานระบบ FLEXJOB']);
    fputcsv($output, ['ช่วงวันที่', $from . ' ถึง ' . $to]);
    fputcsv($output, []);
    fputcsv($output, ['สรุปข้อมูล', 'จำนวน']);
    foreach ([
        'ผู้ว่าจ้างสมัครใหม่' => $stats['employers'],
        'ผู้หางานสมัครใหม่' => $stats['workers'],
        'ประกาศงานใหม่' => $stats['jobs'],
        'ใบสมัครใหม่' => $stats['applications'],
        'งานเสร็จสิ้น' => $stats['completed'],
        'สมาชิก Pro ที่อนุมัติ' => $stats['pro_approved'],
        'สิทธิ์ดันโพสต์ Pro ที่ใช้' => $stats['pro_promotions'],
        'รายได้ Pro ที่อนุมัติ (บาท)' => number_format($stats['revenue'], 2, '.', ''),
    ] as $label => $value) fputcsv($output, [$label, $value]);
    fputcsv($output, []);
    fputcsv($output, ['ประเภทงาน', 'ประกาศใหม่', 'ใบสมัครใหม่']);
    foreach ($categories as $category) {
        fputcsv($output, [job_type($category['category_slug']), $category['job_count'], $category['application_count']]);
    }
    fputcsv($output, []);
    fputcsv($output, ['สถานะประกาศ', 'จำนวนประกาศใหม่']);
    foreach ($jobStatuses as $status) {
        fputcsv($output, [$statusLabels[$status['job_status']] ?? $status['job_status'], $status['job_count']]);
    }
    fputcsv($output, []);
    fputcsv($output, ['สถานะสมาชิก Pro', 'จำนวนรายการ']);
    foreach ($subscriptionStatuses as $status) {
        fputcsv($output, [$subscriptionStatusLabels[$status['subscription_status']] ?? $status['subscription_status'], $status['subscription_count']]);
    }
    fclose($output);
    exit;
}

$pageTitle = $printView ? 'รายงานระบบ FLEXJOB' : 'รายงานระบบ | FLEXJOB';
$pageStyles = $printView ? ['admin-report-print'] : ['admin-reports'];
$pageScripts = $printView ? ['admin-report-print'] : [];
require APP_ROOT . '/partials/header.php';
?>

<?php if ($printView): ?>
<main id="content" class="admin-report-print-view" tabindex="-1">
    <article class="admin-report-paper">
        <header class="admin-report-paper-header">
            <div class="admin-report-paper-brand"><span>F</span><strong>FLEXJOB</strong></div>
            <div class="admin-report-paper-title"><p>ADMINISTRATOR REPORT</p><h1>รายงานสรุประบบ FLEXJOB</h1><span>ช่วงวันที่ <?= date('d/m/Y', strtotime($from)) ?> - <?= date('d/m/Y', strtotime($to)) ?></span></div>
            <div class="admin-report-paper-meta"><strong>REPORT</strong><span>สร้างเมื่อ <?= date('d/m/Y H:i') ?></span></div>
        </header>

        <section class="admin-report-paper-summary">
            <?php foreach ([
                ['ผู้ว่าจ้างใหม่', $stats['employers'], 'บัญชี'],
                ['ผู้หางานใหม่', $stats['workers'], 'บัญชี'],
                ['ประกาศงานใหม่', $stats['jobs'], 'ประกาศ'],
                ['ใบสมัครใหม่', $stats['applications'], 'ใบสมัคร'],
                ['งานเสร็จสิ้น', $stats['completed'], 'รายการ'],
                ['สมาชิก Pro ที่อนุมัติ', $stats['pro_approved'], 'รายการ'],
                ['สิทธิ์ดันโพสต์ Pro', $stats['pro_promotions'], 'ครั้ง'],
                ['รายได้ Pro ที่อนุมัติ', '฿' . number_format($stats['revenue'], 2), 'สมาชิก Pro'],
            ] as [$label, $value, $hint]): ?>
                <div><span><?= e($label) ?></span><strong><?= e((string) $value) ?></strong><small><?= e($hint) ?></small></div>
            <?php endforeach; ?>
        </section>

        <section class="admin-report-paper-section">
            <div class="admin-report-paper-section-heading"><p>01</p><div><h2>ประกาศและการสมัครตามประเภทงาน</h2><span>ข้อมูลที่เกิดขึ้นภายในช่วงวันที่เลือก</span></div></div>
            <table>
                <thead><tr><th>ประเภทงาน</th><th>ประกาศใหม่</th><th>ใบสมัครใหม่</th></tr></thead>
                <tbody>
                    <?php if ($categories): foreach ($categories as $category): ?><tr><td><?= e(job_type($category['category_slug'])) ?></td><td><?= number_format((int) $category['job_count']) ?></td><td><?= number_format((int) $category['application_count']) ?></td></tr><?php endforeach; else: ?><tr><td colspan="3" class="admin-report-paper-empty">ยังไม่มีข้อมูลในช่วงวันที่เลือก</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="admin-report-paper-section admin-report-paper-status-section">
            <div class="admin-report-paper-section-heading"><p>02</p><div><h2>สถานะประกาศใหม่</h2><span>นับเฉพาะประกาศที่สร้างในช่วงวันที่เลือก</span></div></div>
            <?php if ($jobStatuses): ?><div class="admin-report-paper-statuses"><?php foreach ($jobStatuses as $status): ?><div><span><?= e($statusLabels[$status['job_status']] ?? $status['job_status']) ?></span><strong><?= number_format((int) $status['job_count']) ?></strong></div><?php endforeach; ?></div><?php else: ?><p class="admin-report-paper-empty">ยังไม่มีประกาศใหม่</p><?php endif; ?>
        </section>

        <section class="admin-report-paper-section admin-report-paper-status-section">
            <div class="admin-report-paper-section-heading"><p>03</p><div><h2>สถานะสมาชิก Pro</h2><span>สถานะปัจจุบันของรายการที่สร้างในช่วงวันที่เลือก</span></div></div>
            <?php if ($subscriptionStatuses): ?><div class="admin-report-paper-statuses"><?php foreach ($subscriptionStatuses as $status): ?><div><span><?= e($subscriptionStatusLabels[$status['subscription_status']] ?? $status['subscription_status']) ?></span><strong><?= number_format((int) $status['subscription_count']) ?></strong></div><?php endforeach; ?></div><?php else: ?><p class="admin-report-paper-empty">ยังไม่มีรายการสมาชิก Pro</p><?php endif; ?>
        </section>

        <footer class="admin-report-paper-footer">
            <div><span>จัดทำโดย</span><strong>ผู้ดูแลระบบ FLEXJOB</strong></div>
            <div><span>เอกสารฉบับนี้สร้างจากข้อมูลในระบบ ณ วันที่จัดทำรายงาน</span><strong>FLEXJOB - งานที่ใช่ ในเวลาที่ยืดหยุ่น</strong></div>
        </footer>
    </article>
    <div class="admin-report-print-actions"><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/reports.php?from=<?= e($from) ?>&amp;to=<?= e($to) ?>">กลับหน้ารายงาน</a><button class="btn btn-primary" type="button" data-print-report>พิมพ์ / บันทึกเป็น PDF</button></div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
<?php else: ?>

<main id="content" class="admin-reports" tabindex="-1">
    <div class="container">
        <header class="admin-reports-hero card border-0 mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-4">
                    <div>
                        <p class="admin-reports-eyebrow mb-2">SYSTEM REPORTS</p>
                        <h1 class="display-6 mb-2">รายงานระบบ FLEXJOB</h1>
                        <p class="text-secondary mb-0">สรุปการเติบโตของผู้ใช้ ประกาศงาน การสมัคร และรายได้จากรายการที่อนุมัติแล้ว</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/admin/reports.php?from=<?= e($from) ?>&amp;to=<?= e($to) ?>&amp;print=1">บันทึกเป็น PDF</a><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/reports.php?from=<?= e($from) ?>&amp;to=<?= e($to) ?>&amp;export=csv">ดาวน์โหลด CSV</a></div>
                </div>
            </div>
        </header>

        <section class="card border-0 admin-report-filter mb-4" aria-label="ตัวกรองรายงาน">
            <div class="card-body p-3 p-lg-4">
                <form class="row g-3 align-items-end" method="get">
                    <div class="col-sm-5 col-lg-3">
                        <label class="form-label" for="report-from">ตั้งแต่วันที่</label>
                        <input class="form-control" id="report-from" type="date" name="from" value="<?= e($from) ?>" max="<?= e($to) ?>">
                    </div>
                    <div class="col-sm-5 col-lg-3">
                        <label class="form-label" for="report-to">ถึงวันที่</label>
                        <input class="form-control" id="report-to" type="date" name="to" value="<?= e($to) ?>" min="<?= e($from) ?>">
                    </div>
                    <div class="col-sm-2 col-lg-auto d-grid">
                        <button class="btn btn-primary" type="submit">แสดงรายงาน</button>
                    </div>
                    <div class="col-12 col-lg text-lg-end">
                        <span class="admin-report-period">ข้อมูลระหว่าง <?= date('d/m/Y', strtotime($from)) ?> – <?= date('d/m/Y', strtotime($to)) ?></span>
                    </div>
                </form>
            </div>
        </section>

        <section class="mb-4" aria-labelledby="report-summary-heading">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div><p class="admin-reports-eyebrow mb-1">PERIOD SUMMARY</p><h2 class="h3 mb-0" id="report-summary-heading">ภาพรวมตามช่วงวันที่</h2></div>
                <small class="text-secondary">อ้างอิงวันที่สร้างรายการ ยกเว้นรายได้อ้างอิงวันอนุมัติ</small>
            </div>
            <div class="row g-3">
                <?php foreach ([
                    ['ผู้ว่าจ้างใหม่', $stats['employers'], 'บัญชี', 'blue'],
                    ['ผู้หางานใหม่', $stats['workers'], 'บัญชี', 'sky'],
                    ['ประกาศงานใหม่', $stats['jobs'], 'ประกาศ', 'indigo'],
                    ['ใบสมัครใหม่', $stats['applications'], 'ใบสมัคร', 'violet'],
                    ['งานเสร็จสิ้น', $stats['completed'], 'รายการ', 'green'],
                    ['สมาชิก Pro ที่อนุมัติ', $stats['pro_approved'], 'รายการ', 'blue'],
                    ['สิทธิ์ดันโพสต์ Pro', $stats['pro_promotions'], 'ครั้ง', 'indigo'],
                    ['รายได้ Pro ที่อนุมัติ', '฿' . number_format($stats['revenue'], 2), 'สมาชิก Pro', 'amber'],
                ] as [$label, $value, $hint, $tone]): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <article class="card border-0 h-100 admin-report-stat admin-report-stat-<?= e($tone) ?>">
                            <div class="card-body p-3 p-lg-4">
                                <p class="mb-1"><?= e($label) ?></p>
                                <strong><?= e((string) $value) ?></strong>
                                <small><?= e($hint) ?></small>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card border-0 admin-report-card mb-5" aria-labelledby="report-pro-heading">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4"><p class="admin-reports-eyebrow mb-1">PRO SUBSCRIPTIONS</p><h2 class="h4 mb-1" id="report-pro-heading">สถานะสมาชิก Pro</h2><p class="text-secondary mb-0">สถานะปัจจุบันของรายการที่สร้างในช่วงวันที่เลือก</p></div>
                <?php if ($subscriptionStatuses): ?><ul class="admin-report-status-list mb-0" role="list"><?php foreach ($subscriptionStatuses as $status): ?>
                    <li><span><?= e($subscriptionStatusLabels[$status['subscription_status']] ?? $status['subscription_status']) ?></span><strong><?= number_format((int) $status['subscription_count']) ?></strong></li>
                <?php endforeach; ?></ul><?php else: ?><p class="text-secondary mb-0">ยังไม่มีรายการสมาชิก Pro ในช่วงวันที่เลือก</p><?php endif; ?>
            </div>
        </section>

        <section class="row g-4 mb-5">
            <div class="col-xl-8">
                <article class="card border-0 admin-report-card h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                            <div><p class="admin-reports-eyebrow mb-1">CATEGORY PERFORMANCE</p><h2 class="h4 mb-1">ประกาศและการสมัครตามประเภทงาน</h2><p class="text-secondary mb-0">เปรียบเทียบจำนวนรายการที่เกิดขึ้นในช่วงวันที่เลือก</p></div>
                        </div>
                        <?php if ($categories): ?>
                            <div class="table-responsive">
                                <table class="table align-middle admin-report-table mb-0">
                                    <thead><tr><th scope="col">ประเภทงาน</th><th scope="col" class="text-end">ประกาศใหม่</th><th scope="col" class="text-end">ใบสมัครใหม่</th></tr></thead>
                                    <tbody><?php foreach ($categories as $category): ?><tr>
                                        <td><?= e(job_type($category['category_slug'])) ?></td>
                                        <td class="text-end"><?= number_format((int) $category['job_count']) ?></td>
                                        <td class="text-end"><?= number_format((int) $category['application_count']) ?></td>
                                    </tr><?php endforeach; ?></tbody>
                                </table>
                            </div>
                        <?php else: ?><p class="text-secondary mb-0">ยังไม่มีข้อมูลในช่วงวันที่เลือก</p><?php endif; ?>
                    </div>
                </article>
            </div>
            <div class="col-xl-4">
                <article class="card border-0 admin-report-card h-100">
                    <div class="card-body p-4 p-lg-5">
                        <p class="admin-reports-eyebrow mb-1">JOB STATUS</p>
                        <h2 class="h4 mb-1">สถานะประกาศใหม่</h2>
                        <p class="text-secondary mb-4">นับเฉพาะงานที่สร้างในช่วงวันที่เลือก</p>
                        <?php if ($jobStatuses): ?><ul class="admin-report-status-list mb-0" role="list"><?php foreach ($jobStatuses as $status): ?>
                            <li><span><?= e($statusLabels[$status['job_status']] ?? $status['job_status']) ?></span><strong><?= number_format((int) $status['job_count']) ?></strong></li>
                        <?php endforeach; ?></ul><?php else: ?><p class="text-secondary mb-0">ยังไม่มีประกาศใหม่</p><?php endif; ?>
                    </div>
                </article>
            </div>
        </section>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
<?php endif; ?>
