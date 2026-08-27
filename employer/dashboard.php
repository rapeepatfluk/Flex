<?php require_once __DIR__ . '/../config/config.php';
require_login('employer');
$pdo = db();
$profileStmt = $pdo->prepare("SELECT ep.*, COALESCE((SELECT ed.document_status FROM employer_documents ed WHERE ed.employer_user_id=ep.user_id ORDER BY ed.submitted_at DESC LIMIT 1),'not_submitted') AS verification_status FROM employer_profiles ep WHERE ep.user_id=?");
$profileStmt->execute([user()['id']]);
$profile = $profileStmt->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (($_POST['action'] ?? '') === 'document') {
            $file = upload_file('criminal_record', ['pdf', 'jpg', 'jpeg', 'png'], 'verification');
            if (!$file) throw new RuntimeException('กรุณาเลือกเอกสาร');
            $pdo->prepare("INSERT INTO employer_documents (employer_user_id,document_file_path,document_status) VALUES (?,?,'pending')")->execute([user()['id'], $file]);
            flash('success', 'ส่งเอกสารแล้ว กรุณารอ Admin ตรวจสอบ');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('employer/dashboard.php');
}
$jobsStmt = $pdo->prepare("SELECT j.job_id AS id,j.job_title AS title,jc.category_slug AS job_type,wi.interest_name work_interest_name,j.pay_amount,j.pay_unit,j.job_status AS status,COUNT(DISTINCT a.application_id) applicants,COUNT(DISTINCT js.skill_id) matching_skills FROM jobs j JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id LEFT JOIN applications a ON a.job_id=j.job_id LEFT JOIN job_skills js ON js.job_id=j.job_id WHERE j.employer_user_id=? GROUP BY j.job_id ORDER BY j.created_at DESC");
$jobsStmt->execute([user()['id']]);
$jobs = $jobsStmt->fetchAll();
$pageTitle = 'ผู้ว่าจ้าง | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="dashboard">
    <div class="dashboard-title">
        <div>
            <p class="eyebrow">EMPLOYER DASHBOARD</p>
            <h1><?= e($profile['company_name']) ?></h1>
            <p>จัดการการยืนยันตัวตน ประกาศงาน และผู้สมัคร</p>
        </div><a class="btn btn-primary" href="<?= BASE_URL ?>/employer/jobpost.php">+ สร้างประกาศงาน</a>
    </div>
    <section class="verification <?= e($profile['verification_status']) ?>">
        <div><b>สถานะการยืนยันผู้ว่าจ้าง: <?= ['not_submitted' => 'ยังไม่ได้ส่งเอกสาร', 'pending' => 'รอ Admin ตรวจสอบ', 'approved' => 'ยืนยันแล้ว', 'rejected' => 'เอกสารไม่ผ่าน', 'resubmit' => 'ต้องส่งเอกสารใหม่'][$profile['verification_status']] ?></b>
            <p>เพื่อความปลอดภัย Employer ต้องยื่นเอกสารประวัติอาชญากรรมและผ่านการตรวจสอบก่อนโพสต์หรือจ้างงาน</p>
        </div><?php if ($profile['verification_status'] !== 'approved'): ?><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="document"><input type="file" name="criminal_record" accept=".pdf,.jpg,.jpeg,.png" required><button class="btn btn-primary">ส่งเอกสาร</button></form><?php else: ?><span class="status accepted">✓ ผ่านการยืนยัน</span><?php endif ?>
    </section>
    <section class="panel">
        <h2>ประกาศงานของคุณ <span class="count"><?= count($jobs) ?></span></h2><?php foreach ($jobs as $job): ?><div class="application">
                    <div><b><?= e($job['title']) ?></b>
                        <p><?= job_type($job['job_type']) ?> · <?= e($job['work_interest_name'] ?: 'ยังไม่ได้เลือกหมวดงาน') ?> · <?= pay_text($job) ?></p><small>ผู้สมัคร <?= e((string)$job['applicants']) ?> คน · <?= $job['status'] === 'published' ? 'เผยแพร่แล้ว' : 'ปิดแล้ว' ?> · <?= $job['matching_skills'] ? 'มีข้อมูล Matching ' . e((string) $job['matching_skills']) . ' ทักษะ' : 'ยังไม่มีข้อมูล Matching' ?></small><?php if (!$job['work_interest_name']): ?><div class="text-warning small mt-1">ควรแก้ไขประกาศและเลือกหมวดงาน เพื่อให้ Matching แม่นยำขึ้น</div><?php endif ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/employer/applicants.php?job=<?= $job['id'] ?>">ดูผู้สมัคร</a>
                        <a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/employer/candidates.php?job=<?= $job['id'] ?>">ค้นหาคนที่เหมาะ</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/employer/jobedit.php?id=<?= $job['id'] ?>">แก้ไข</a>
                        <form method="post" action="<?= BASE_URL ?>/employer/jobdelete.php" onsubmit="return confirm('ยืนยันการลบประกาศงานนี้? ข้อมูลผู้สมัครของประกาศนี้จะถูกลบด้วย');"><?= csrf_field() ?><input type="hidden" name="job_id" value="<?= $job['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button></form>
                    </div>
                </div><?php endforeach ?><?php if (!$jobs): ?><div class="empty">ยังไม่มีประกาศงาน</div><?php endif ?>
    </section>
</main><?php require APP_ROOT . '/partials/footer.php'; ?>
