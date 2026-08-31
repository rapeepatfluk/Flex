<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$pdo = db();
$employerId = (int) ($_GET['id'] ?? 0);

$profileStmt = $pdo->prepare("SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.account_status, u.created_at, ep.company_name, ep.company_description, ep.company_address, ep.company_logo_path FROM users u JOIN employer_profiles ep ON ep.user_id=u.user_id WHERE u.user_id=? AND u.role='employer'");
$profileStmt->execute([$employerId]);
$employer = $profileStmt->fetch();

if (!$employer) {
    flash('error', 'ไม่พบข้อมูลผู้ว่าจ้าง');
    redirect('admin/users.php');
}

$documentStmt = $pdo->prepare('SELECT employer_document_id,document_file_path,document_status,review_note,submitted_at,reviewed_at FROM employer_documents WHERE employer_user_id=? ORDER BY submitted_at DESC LIMIT 1');
$documentStmt->execute([$employerId]);
$document = $documentStmt->fetch();

$jobsStmt = $pdo->prepare('SELECT j.job_id, j.job_title, j.job_status, j.created_at, COUNT(a.application_id) AS applicants FROM jobs j LEFT JOIN applications a ON a.job_id=j.job_id WHERE j.employer_user_id=? GROUP BY j.job_id ORDER BY j.created_at DESC');
$jobsStmt->execute([$employerId]);
$jobs = $jobsStmt->fetchAll();

$documentLabels = [
    'pending' => 'รอตรวจสอบ',
    'approved' => 'ผ่านการตรวจสอบ',
    'rejected' => 'ไม่ผ่านการตรวจสอบ',
    'resubmit' => 'ต้องส่งเอกสารเพิ่ม',
];

$pageTitle = 'ข้อมูลผู้ว่าจ้าง | FLEXJOB';
$pageStyles = ['admin-employer'];
require APP_ROOT . '/partials/header.php';
?>

<main id="content" class="container py-5 admin-employer" tabindex="-1">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow">EMPLOYER PROFILE</p>
            <h1 class="h2 mb-1">ข้อมูลผู้ว่าจ้าง</h1>
            <p class="text-secondary mb-0">ตรวจสอบข้อมูลบริษัท เอกสาร และประกาศงาน</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/users.php?role=employer">กลับไปจัดการบัญชี</a>
    </div>

    <section class="card border-0 shadow-sm mb-4 admin-employer-profile-card">
        <div class="card-body p-4 p-lg-5">
            <div class="admin-employer-profile-layout">
                <div class="admin-employer-company-logo" aria-hidden="<?= $employer['company_logo_path'] ? 'false' : 'true' ?>">
                    <?php if ($employer['company_logo_path']): ?>
                        <img src="<?= BASE_URL . '/' . e($employer['company_logo_path']) ?>" alt="โลโก้ <?= e($employer['company_name']) ?>">
                    <?php else: ?>
                        <span><?= e(mb_substr($employer['company_name'], 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="admin-employer-profile-content">
                    <div class="admin-employer-profile-heading">
                        <div>
                            <p class="admin-employer-profile-label mb-2">COMPANY PROFILE</p>
                            <h2 class="h3 mb-2"><?= e($employer['company_name']) ?></h2>
                            <p class="admin-employer-profile-description mb-0"><?= e($employer['company_description'] ?: 'ยังไม่มีรายละเอียดบริษัท') ?></p>
                        </div>
                        <span class="admin-employer-profile-status <?= $employer['account_status'] === 'active' ? 'is-active' : 'is-suspended' ?>"><span aria-hidden="true"><?= $employer['account_status'] === 'active' ? '✓' : '!' ?></span><?= $employer['account_status'] === 'active' ? 'บัญชีใช้งานอยู่' : 'บัญชีถูกระงับ' ?></span>
                    </div>
                    <dl class="admin-employer-contact-grid mb-0">
                        <div class="admin-employer-contact-item"><dt>ผู้ติดต่อ</dt><dd><?= e($employer['first_name'] . ' ' . $employer['last_name']) ?></dd></div>
                        <div class="admin-employer-contact-item"><dt>อีเมล</dt><dd><a href="mailto:<?= e($employer['email']) ?>"><?= e($employer['email']) ?></a></dd></div>
                        <div class="admin-employer-contact-item"><dt>เบอร์โทรศัพท์</dt><dd><?= e($employer['phone'] ?: '-') ?></dd></div>
                        <div class="admin-employer-contact-item admin-employer-contact-address"><dt>ที่อยู่บริษัท</dt><dd><?= e($employer['company_address'] ?: '-') ?></dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </section>


    <div class="row g-4">
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm h-100"><div class="card-body p-4">
                <h2 class="h5 mb-3">เอกสารยืนยันตัวตน</h2>
                <?php if ($document): ?>
                    <p class="mb-2"><span class="badge text-bg-light border"><?= e($documentLabels[$document['document_status']] ?? $document['document_status']) ?></span></p>
                    <p class="text-secondary small">ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($document['submitted_at'])) ?></p>
                    <?php if ($document['review_note']): ?><p class="small mb-3">หมายเหตุ: <?= e($document['review_note']) ?></p><?php endif; ?>
                    <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=employer_document&id=<?= $document['employer_document_id'] ?>">เปิดเอกสาร</a>
                <?php else: ?>
                    <p class="text-secondary mb-0">ยังไม่ได้ส่งเอกสารยืนยันตัวตน</p>
                <?php endif; ?>
            </div></section>
        </div>
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm h-100"><div class="card-body p-4">
                <h2 class="h5 mb-3">ประกาศงานของผู้ว่าจ้าง (<?= count($jobs) ?>)</h2>
                <?php foreach ($jobs as $job): ?>
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 border-top py-3">
                        <div><b><?= e($job['job_title']) ?></b><p class="text-secondary small mb-0">ผู้สมัคร <?= e((string) $job['applicants']) ?> คน · <?= date('d/m/Y', strtotime($job['created_at'])) ?></p></div>
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/job.php?id=<?= $job['job_id'] ?>">ดูประกาศ</a>
                    </div>
                <?php endforeach; ?>
                <?php if (!$jobs): ?><p class="text-secondary mb-0">ยังไม่มีประกาศงาน</p><?php endif; ?>
            </div></section>
        </div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
