<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$pdo = db();
$stats = [
    'employers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='employer'")->fetchColumn(),
    'workers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='worker'")->fetchColumn(),
    'jobs' => $pdo->query("SELECT COUNT(*) FROM jobs WHERE job_status='published'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM employer_documents WHERE document_status IN ('pending', 'resubmit')")->fetchColumn(),
];
$smtpConfigured = SMTP_USER !== '' && SMTP_PASS !== '';

$pageTitle = 'Admin | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="mb-4">
        <p class="eyebrow">ADMIN CONTROL CENTER</p>
        <h1 class="h2 mb-1">ภาพรวมระบบ</h1>
        <p class="text-secondary mb-0">เลือกส่วนที่ต้องการจัดการ</p>
    </div>
    <?php if (!$smtpConfigured): ?><div class="alert alert-warning"><b>ยังไม่ได้ตั้งค่า Email</b> — การแจ้งเตือนในเว็บไซต์ยังทำงาน แต่ระบบจะข้ามการส่ง Email จนกว่าจะกำหนด <code>FLEXJOB_SMTP_USER</code> และ <code>FLEXJOB_SMTP_PASS</code></div><?php endif ?>

    <div class="row g-3 mb-5">
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 bg-primary text-white shadow-sm">
                <div class="card-body"><small class="text-white-50 fw-semibold">ผู้ว่าจ้าง</small><strong class="d-block fs-3 text-white"><?= $stats['employers'] ?></strong></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 bg-primary text-white shadow-sm">
                <div class="card-body"><small class="text-white-50 fw-semibold">ผู้หางาน</small><strong class="d-block fs-3 text-white"><?= $stats['workers'] ?></strong></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 bg-primary text-white shadow-sm">
                <div class="card-body"><small class="text-white-50 fw-semibold">งานเผยแพร่</small><strong class="d-block fs-3 text-white"><?= $stats['jobs'] ?></strong></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 bg-primary text-white shadow-sm">
                <div class="card-body"><small class="text-white-50 fw-semibold">เอกสารรอตรวจ</small><strong class="d-block fs-3 text-white"><?= $stats['pending'] ?></strong></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6"><a class="card h-100 border-0 shadow-sm text-decoration-none" href="<?= BASE_URL ?>/admin/documents.php">
                <div class="card-body p-4">
                    <p class="eyebrow mb-2">VERIFICATION</p>
                    <h2 class="h4">เอกสารผู้ว่าจ้าง</h2>
                    <p class="text-secondary mb-3">ตรวจเอกสารประวัติอาชญากรรม อนุมัติ ขอเอกสารเพิ่ม หรือไม่ผ่าน</p><span class="btn btn-primary">ตรวจสอบ <?= $stats['pending'] ?> รายการ</span>
                </div>
            </a></div>
        <div class="col-md-6"><a class="card h-100 border-0 shadow-sm text-decoration-none" href="<?= BASE_URL ?>/admin/jobs.php">
                <div class="card-body p-4">
                    <p class="eyebrow mb-2">JOB MODERATION</p>
                    <h2 class="h4">ตรวจสอบประกาศงาน</h2>
                    <p class="text-secondary mb-3">ตรวจย้อนหลัง ซ่อน ปิดรับสมัคร หรือลบประกาศที่ไม่เหมาะสม</p><span class="btn btn-primary">จัดการประกาศ</span>
                </div>
            </a></div>
        <div class="col-md-6"><a class="card h-100 border-0 shadow-sm text-decoration-none" href="<?= BASE_URL ?>/admin/users.php">
                <div class="card-body p-4">
                    <p class="eyebrow mb-2">ACCOUNT MANAGEMENT</p>
                    <h2 class="h4">จัดการบัญชีผู้ใช้</h2>
                    <p class="text-secondary mb-3">เปิดใช้งานหรือระงับบัญชี Worker และ Employer</p><span class="btn btn-primary">จัดการบัญชี</span>
                </div>
            </a></div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
