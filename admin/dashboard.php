<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$pdo = db();
$stats = [
    'employers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='employer'")->fetchColumn(),
    'workers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='worker'")->fetchColumn(),
    'jobs' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE job_status='published'")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM employer_documents WHERE document_status IN ('pending','resubmit')")->fetchColumn(),
    'promotion_pending' => (int) $pdo->query("SELECT COUNT(*) FROM job_promotions WHERE promotion_status='pending_verification'")->fetchColumn(),
];
$pendingDocuments = $pdo->query("SELECT ed.employer_user_id,ed.document_status,ed.submitted_at,ep.company_name,CONCAT(u.first_name,' ',u.last_name) contact_name FROM employer_documents ed JOIN employer_profiles ep ON ep.user_id=ed.employer_user_id JOIN users u ON u.user_id=ed.employer_user_id WHERE ed.document_status IN ('pending','resubmit') ORDER BY ed.submitted_at DESC LIMIT 4")->fetchAll();
$recentJobs = $pdo->query("SELECT j.job_id,j.job_status,j.job_title,j.created_at,ep.company_name,wi.interest_name work_interest_name FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id ORDER BY j.created_at DESC LIMIT 4")->fetchAll();
$documentStatus = ['pending' => ['รอตรวจสอบ', 'warning'], 'resubmit' => ['ส่งเอกสารใหม่', 'info']];
$jobStatus = ['published' => ['เผยแพร่แล้ว', 'success'], 'hidden' => ['ซ่อนประกาศ', 'secondary'], 'closed' => ['ปิดรับสมัคร', 'dark']];

$pageTitle = 'ศูนย์ควบคุมผู้ดูแล | FLEXJOB';
$pageStyles = ['admin-dashboard'];
require APP_ROOT . '/partials/header.php';
?>

<main id="content" class="admin-dashboard" tabindex="-1">
    <div class="container">
        <header class="admin-dashboard-hero card border-0 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg">
                        <p class="admin-dashboard-eyebrow mb-2">ADMIN CONTROL CENTER</p>
                        <h1 class="display-6 mb-2">ภาพรวมระบบ FLEXJOB</h1>
                        <p class="admin-dashboard-lead mb-0">ติดตามสิ่งที่ต้องตรวจสอบ และเข้าถึงงานดูแลระบบสำคัญได้ในหน้าเดียว</p>
                    </div>
                    <div class="col-lg-auto"><div class="admin-dashboard-hero-actions">
                        <a class="btn btn-primary" href="<?= BASE_URL ?>/admin/documents.php">ตรวจเอกสารผู้ว่าจ้าง</a>
                        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/promotions.php">ตรวจสอบการชำระเงิน<?= $stats['promotion_pending'] ? ' (' . number_format($stats['promotion_pending']) . ')' : '' ?></a>
                        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/users.php">จัดการบัญชี</a>
                    </div></div>
                </div>
            </div>
        </header>

        <?php if (!mail_is_configured()): ?>
            <aside class="alert admin-smtp-alert mb-4" role="alert"><span class="admin-alert-icon" aria-hidden="true">!</span><div><strong>การส่งอีเมลยังไม่พร้อมใช้งาน</strong><p class="mb-0">การแจ้งเตือนในเว็บไซต์ยังทำงานตามปกติ แต่ต้องตั้งค่า <code>FLEXJOB_SMTP_USER</code> และ <code>FLEXJOB_SMTP_PASS</code> ก่อนส่งอีเมลให้ผู้ใช้</p></div></aside>
        <?php endif; ?>

        <section class="mb-4" aria-labelledby="admin-summary-heading">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-2 mb-3"><div><p class="admin-dashboard-eyebrow mb-1">SYSTEM SNAPSHOT</p><h2 class="h3 mb-0" id="admin-summary-heading">สรุปข้อมูลระบบ</h2></div><p class="small text-secondary mb-0">อัปเดตจากฐานข้อมูลปัจจุบัน</p></div>
            <div class="row g-3">
                <?php foreach ([
                    ['employers', '▣', 'ผู้ว่าจ้าง', 'บัญชีทั้งหมด', 'blue'],
                    ['workers', '◎', 'ผู้หางาน', 'บัญชีทั้งหมด', 'sky'],
                    ['jobs', '◷', 'งานเผยแพร่', 'ประกาศที่เปิดอยู่', 'indigo'],
                    ['pending', '!', 'เอกสารรอตรวจ', 'ต้องดำเนินการ', 'amber'],
                ] as [$key, $icon, $label, $hint, $tone]): ?>
                    <div class="col-6 col-lg-3"><article class="card border-0 h-100 admin-stat-card admin-stat-<?= $tone ?>"><div class="card-body p-3 p-lg-4"><span class="admin-stat-icon" aria-hidden="true"><?= $icon ?></span><p class="mb-1"><?= $label ?></p><strong><?= number_format($stats[$key]) ?></strong><small><?= $hint ?></small></div></article></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-priority card border-0 mb-4" aria-labelledby="admin-priority-heading">
            <div class="card-body p-4 p-lg-5"><div class="row align-items-center g-4"><div class="col-lg"><div class="d-flex align-items-start gap-3"><span class="admin-priority-icon" aria-hidden="true"><?= $stats['pending'] ? '!' : '✓' ?></span><div><p class="admin-dashboard-eyebrow mb-1">PRIORITY QUEUE</p><h2 class="h4 mb-1" id="admin-priority-heading"><?= $stats['pending'] ? 'มีเอกสารที่รอการตรวจสอบ' : 'ไม่มีเอกสารที่รอตรวจสอบ' ?></h2><p class="mb-0 text-secondary"><?= $stats['pending'] ? 'ตรวจเอกสารผู้ว่าจ้างเพื่อให้บัญชีพร้อมใช้งาน' : 'รายการเอกสารได้รับการจัดการครบถ้วนในขณะนี้' ?></p></div></div></div><div class="col-lg-auto"><a class="btn <?= $stats['pending'] ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= BASE_URL ?>/admin/documents.php"><?= $stats['pending'] ? 'ตรวจสอบ ' . number_format($stats['pending']) . ' รายการ' : 'ดูเอกสารทั้งหมด' ?></a></div></div></div>
        </section>

        <section class="mb-5" aria-labelledby="admin-actions-heading">
            <p class="admin-dashboard-eyebrow mb-1">QUICK ACTIONS</p><h2 class="h3 mb-3" id="admin-actions-heading">จัดการระบบ</h2>
            <div class="row g-3">
                <?php foreach ([
                    ['documents.php', '▤', 'VERIFICATION', 'เอกสารผู้ว่าจ้าง', 'อนุมัติ ขอเอกสารเพิ่ม หรือแจ้งผลการตรวจสอบให้ผู้ว่าจ้างทราบ', 'ตรวจสอบเอกสาร'],
                    ['promotions.php', '✦', 'PROMOTION PAYMENTS', 'การโปรโมตประกาศ', 'ตรวจสอบสลิป อนุมัติการชำระเงิน และติดตามระยะเวลาโปรโมต', 'ตรวจสอบการชำระเงิน'],
                    ['jobs.php', '◷', 'JOB MODERATION', 'ประกาศงาน', 'ตรวจสอบเนื้อหา ซ่อน ปิดรับสมัคร หรือลบประกาศที่ไม่เหมาะสม', 'จัดการประกาศ'],
                    ['users.php', '◎', 'ACCOUNT MANAGEMENT', 'บัญชีผู้ใช้', 'ค้นหา ตรวจสอบสถานะ และเปิดหรือระงับบัญชีผู้ใช้งาน', 'จัดการบัญชี'],
                ] as [$path, $icon, $eyebrow, $title, $description, $action]): ?>
                    <div class="col-md-6 col-xl-4"><a class="card border-0 h-100 admin-action-card" href="<?= BASE_URL ?>/admin/<?= $path ?>"><div class="card-body p-4"><span class="admin-action-icon" aria-hidden="true"><?= $icon ?></span><p class="admin-dashboard-eyebrow mb-2"><?= $eyebrow ?></p><h3 class="h5"><?= $title ?></h3><p class="text-secondary mb-4"><?= $description ?></p><span class="admin-action-link"><?= $action ?> <span aria-hidden="true">→</span></span></div></a></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="row g-4" aria-label="รายการล่าสุด">
            <div class="col-lg-6"><article class="card border-0 admin-list-card h-100"><div class="card-body p-4 p-lg-5"><div class="d-flex align-items-start justify-content-between gap-3 mb-4"><div><p class="admin-dashboard-eyebrow mb-1">VERIFICATION QUEUE</p><h2 class="h4 mb-0">เอกสารที่ต้องตรวจ</h2></div><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/documents.php">ทั้งหมด</a></div>
                <?php if ($pendingDocuments): ?><ul class="admin-activity-list mb-0" role="list"><?php foreach ($pendingDocuments as $document): $status = $documentStatus[$document['document_status']] ?? ['รอตรวจสอบ', 'secondary']; ?><li><span class="admin-activity-mark admin-activity-mark-warning" aria-hidden="true">▤</span><div class="flex-grow-1 min-w-0"><a class="admin-activity-title" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $document['employer_user_id'] ?>"><?= e($document['company_name']) ?></a><p><?= e($document['contact_name']) ?> · ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($document['submitted_at'])) ?></p></div><span class="badge text-bg-<?= e($status[1]) ?> rounded-pill text-nowrap"><?= e($status[0]) ?></span></li><?php endforeach; ?></ul><?php else: ?><div class="admin-empty-state text-center py-4"><div aria-hidden="true">✓</div><p class="mb-0">ไม่มีเอกสารที่รอตรวจสอบ</p></div><?php endif; ?>
            </div></article></div>

            <div class="col-lg-6"><article class="card border-0 admin-list-card h-100"><div class="card-body p-4 p-lg-5"><div class="d-flex align-items-start justify-content-between gap-3 mb-4"><div><p class="admin-dashboard-eyebrow mb-1">RECENT JOB POSTS</p><h2 class="h4 mb-0">ประกาศล่าสุด</h2></div><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/jobs.php">ทั้งหมด</a></div>
                <?php if ($recentJobs): ?><ul class="admin-activity-list mb-0" role="list"><?php foreach ($recentJobs as $job): $status = $jobStatus[$job['job_status']] ?? ['ไม่ทราบสถานะ', 'secondary']; ?><li><span class="admin-activity-mark admin-activity-mark-blue" aria-hidden="true">◷</span><div class="flex-grow-1 min-w-0"><a class="admin-activity-title" href="<?= BASE_URL ?>/job.php?id=<?= $job['job_id'] ?>"><?= e($job['job_title']) ?></a><p><?= e($job['company_name']) ?><?= $job['work_interest_name'] ? ' · ' . e($job['work_interest_name']) : '' ?> · <?= date('d/m/Y', strtotime($job['created_at'])) ?></p></div><span class="badge text-bg-<?= e($status[1]) ?> rounded-pill text-nowrap"><?= e($status[0]) ?></span></li><?php endforeach; ?></ul><?php else: ?><div class="admin-empty-state text-center py-4"><div aria-hidden="true">◷</div><p class="mb-0">ยังไม่มีประกาศงาน</p></div><?php endif; ?>
            </div></article></div>
        </section>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
