<?php require_once __DIR__ . '/config/config.php';
$stmt = db()->prepare("SELECT j.job_id AS id,j.job_category_id,j.job_title AS title,j.job_status AS status,jc.category_slug AS job_type,j.job_description AS description,j.work_location AS location,j.work_province,j.work_schedule AS work_date,j.work_mode,j.application_deadline,j.pay_amount,j.pay_unit,j.open_positions AS positions,ep.company_name,ep.company_description,ep.company_logo_path AS company_logo,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order, ji.job_image_id LIMIT 1) AS cover_image FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id WHERE j.job_id=? AND (j.job_status='published' OR ?=1)");
$stmt->execute([(int)($_GET['id'] ?? 0), is_role('admin') ? 1 : 0]);
$job = $stmt->fetch();
if (!$job) {
    flash('error', 'ไม่พบประกาศงาน');
    redirect('jobs.php');
}
$already = false;
$jobSkillStmt = db()->prepare("SELECT GROUP_CONCAT(IF(js.importance='required',s.skill_id,NULL) ORDER BY s.skill_id) required_skill_ids,GROUP_CONCAT(IF(js.importance='preferred',s.skill_id,NULL) ORDER BY s.skill_id) preferred_skill_ids,GROUP_CONCAT(IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') required_skill_names,GROUP_CONCAT(IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') preferred_skill_names FROM job_skills js JOIN skills s ON s.skill_id=js.skill_id WHERE js.job_id=?");
$jobSkillStmt->execute([$job['id']]);
$job = array_merge($job, $jobSkillStmt->fetch() ?: []);
$jobRequirements = matching_calculate($job, []);
$jobMatch = null;
if (is_role('worker')) {
    $s = db()->prepare('SELECT application_id FROM applications WHERE job_id=? AND worker_user_id=?');
    $s->execute([$job['id'], user()['id']]);
    $already = (bool)$s->fetch();
    $workerStmt = db()->prepare("SELECT wp.work_province,wp.preferred_work_mode,GROUP_CONCAT(DISTINCT ws.skill_id ORDER BY ws.skill_id) skill_ids,GROUP_CONCAT(DISTINCT wjp.job_category_id ORDER BY wjp.job_category_id) preference_category_ids FROM worker_profiles wp LEFT JOIN worker_skills ws ON ws.worker_user_id=wp.user_id LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=wp.user_id WHERE wp.user_id=? GROUP BY wp.user_id");
    $workerStmt->execute([user()['id']]);
    $jobMatch = matching_calculate($job, $workerStmt->fetch() ?: []);
}
$isOpen = $job['status'] === 'published' && (!$job['application_deadline'] || $job['application_deadline'] >= date('Y-m-d'));
$pageTitle = e($job['title']) . ' | FLEXJOB';
$pageStyles = ['job-detail', 'matching'];
$backPath = is_role('admin') ? 'admin/jobs.php' : 'jobs.php';
$backLabel = is_role('admin') ? '← กลับไปตรวจสอบประกาศ' : '← กลับไปหน้าค้นหางาน';
require APP_ROOT . '/partials/header.php'; ?>
<main class="container py-5">
    <a class="link-primary d-inline-block mb-4" href="<?= BASE_URL ?>/<?= $backPath ?>"><?= $backLabel ?></a>
    <?php if (is_role('admin') && $job['status'] !== 'published'): ?><div class="alert alert-info">คุณกำลังดูประกาศสถานะ: <?= e($job['status']) ?></div><?php endif; ?>

    <div class="row justify-content-center">
        <article class="col-lg-11 col-xl-10">
            <div class="card border-0 shadow-sm overflow-hidden">
                <?php if (!empty($job['cover_image'])): ?>
                    <div class="job-detail-cover"><img src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>"></div>
                <?php else: ?>
                    <div class="job-detail-cover job-detail-fallback"><?= $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷') ?></div>
                <?php endif ?>

                <div class="card-body p-4 p-md-5">
                    <span class="badge text-bg-light border mb-2"><?= job_type($job['job_type']) ?></span>
                    <h1 class="h2 mb-1"><?= e($job['title']) ?></h1>
                    <p class="text-secondary mb-4"><?php if ($job['company_logo']): ?><img class="company-logo company-logo-detail" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?> <?php if ($job['is_verified']): ?><span class="text-primary">✓ ผู้ว่าจ้างยืนยันแล้ว</span><?php endif ?></p>

                    <?php if ($jobMatch && $jobMatch['score'] !== null): ?><div class="alert alert-success"><b><?= $jobMatch['score'] ?>% Match กับโปรไฟล์ของคุณ</b><div class="small mt-1"><?= e(implode(' · ', $jobMatch['reasons'])) ?></div><?php if ($jobMatch['missing_required']): ?><div class="small mt-1">ทักษะที่ยังไม่ระบุ: <?= e(implode(', ', $jobMatch['missing_required'])) ?></div><?php endif ?></div><?php endif ?>

                    <div class="row g-0 border rounded overflow-hidden mb-4 small">
                        <div class="col-6 border-end border-bottom p-3"><span class="d-block text-secondary">ค่าจ้าง</span><b><?= pay_text($job) ?></b></div>
                        <div class="col-6 border-bottom p-3"><span class="d-block text-secondary">ที่อยู่ร้าน / สถานที่ทำงาน</span><b><?= e($job['location']) ?></b></div>
                        <div class="col-6 border-end p-3"><span class="d-block text-secondary">วันทำงาน</span><b><?= e($job['work_date']) ?></b></div>
                        <div class="col-6 p-3"><span class="d-block text-secondary">ต้องการ</span><b><?= e((string)$job['positions']) ?> คน</b></div>
                    </div>

                    <h2 class="h5 mb-3">รายละเอียดงาน</h2>
                    <p class="text-secondary lh-lg mb-4"><?= nl2br(e($job['description'])) ?></p>
                    <?php if ($jobRequirements['required_skills'] || $jobRequirements['preferred_skills']): ?><h2 class="h5 mb-3">ทักษะที่ต้องการ</h2><div class="skills-wrap mb-4"><?php foreach ($jobRequirements['required_skills'] as $skill): ?><span class="skill-tag"><?= e($skill) ?> · จำเป็น</span><?php endforeach ?><?php foreach ($jobRequirements['preferred_skills'] as $skill): ?><span class="skill-tag"><?= e($skill) ?> · เสริม</span><?php endforeach ?></div><?php endif ?>
                    <h2 class="h5 mb-3">เกี่ยวกับผู้ว่าจ้าง</h2>
                    <p class="text-secondary lh-lg mb-0"><?= e($job['company_description'] ?: 'ผู้ว่าจ้างผ่านการยืนยันตัวตนกับ FLEXJOB แล้ว') ?></p>

                    <?php if (is_role('worker')): ?>
                        <section class="job-application border-top mt-5 pt-4">
                            <h2 class="h4 mb-1">สนใจงานนี้?</h2>
                            <p class="text-secondary mb-3">สมัครงานได้ในไม่กี่ขั้นตอน</p>

                            <?php if (!$isOpen): ?>
                                <button class="btn btn-secondary px-4" disabled>ปิดรับสมัครแล้ว</button>
                            <?php elseif ($already): ?>
                                <button class="btn btn-secondary px-4" disabled>คุณสมัครงานนี้แล้ว</button>
                            <?php else: ?>
                                <form action="<?= BASE_URL ?>/apply.php" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                    <label class="form-label" for="cover_note">ข้อความถึงผู้ว่าจ้าง <span class="text-secondary">(ไม่บังคับ)</span></label>
                                    <textarea id="cover_note" class="form-control mb-3" name="cover_note" rows="4" placeholder="แนะนำตัวสั้น ๆ"></textarea>
                                    <button class="btn btn-success px-4" type="submit">สมัครงานนี้</button>
                                </form>
                            <?php endif ?>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
