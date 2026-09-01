<?php
require_once __DIR__ . '/config/config.php';

$statement = db()->prepare("SELECT j.job_id AS id,j.job_category_id,j.work_interest_id,wi.interest_name work_interest_name,j.job_title AS title,j.job_status AS status,jc.category_slug AS job_type,j.job_description AS description,j.work_location AS location,j.work_province,j.work_schedule AS work_date,j.work_mode,j.application_deadline,j.pay_amount,j.pay_unit,j.open_positions AS positions,ep.company_name,ep.company_description,ep.company_logo_path AS company_logo,(SELECT ROUND(AVG(a.rating_by_worker), 1) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) AS employer_rating_average,(SELECT COUNT(a.rating_by_worker) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) AS employer_rating_count,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order,ji.job_image_id LIMIT 1) AS cover_image,EXISTS(SELECT 1 FROM job_promotions jp WHERE jp.job_id=j.job_id AND jp.promotion_status='active' AND jp.starts_at<=NOW() AND jp.ends_at>NOW()) AS is_promoted FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id WHERE j.job_id=? AND (?=1 OR (j.job_status='published' AND j.work_province=?))");
$statement->execute([(int) ($_GET['id'] ?? 0), is_role('admin') ? 1 : 0, FLEXJOB_PROVINCE]);
$job = $statement->fetch();
if (!$job) {
    flash('error', 'ไม่พบประกาศงาน');
    redirect('jobs.php');
}

$applicationStatus = null;
$jobSkillStatement = db()->prepare("SELECT GROUP_CONCAT(IF(js.importance='required',s.skill_id,NULL) ORDER BY s.skill_id) required_skill_ids,GROUP_CONCAT(IF(js.importance='preferred',s.skill_id,NULL) ORDER BY s.skill_id) preferred_skill_ids,GROUP_CONCAT(IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') required_skill_names,GROUP_CONCAT(IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') preferred_skill_names FROM job_skills js JOIN skills s ON s.skill_id=js.skill_id WHERE js.job_id=?");
$jobSkillStatement->execute([$job['id']]);
$job = array_merge($job, $jobSkillStatement->fetch() ?: []);
$jobRequirements = matching_calculate($job, []);
$jobMatch = null;

if (is_role('worker')) {
    $applicationStatement = db()->prepare('SELECT application_status FROM applications WHERE job_id=? AND worker_user_id=?');
    $applicationStatement->execute([$job['id'], user()['id']]);
    $applicationStatus = $applicationStatement->fetchColumn() ?: null;

    $workerStatement = db()->prepare("SELECT wp.work_province,wp.preferred_work_mode,GROUP_CONCAT(DISTINCT ws.skill_id ORDER BY ws.skill_id) skill_ids,GROUP_CONCAT(DISTINCT wjp.job_category_id ORDER BY wjp.job_category_id) preference_category_ids,GROUP_CONCAT(DISTINCT wwi.work_interest_id ORDER BY wwi.work_interest_id) work_interest_ids FROM worker_profiles wp LEFT JOIN worker_skills ws ON ws.worker_user_id=wp.user_id LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=wp.user_id LEFT JOIN worker_work_interests wwi ON wwi.worker_user_id=wp.user_id WHERE wp.user_id=? GROUP BY wp.user_id");
    $workerStatement->execute([user()['id']]);
    $jobMatch = matching_calculate($job, $workerStatement->fetch() ?: []);
}

$isOpen = $job['status'] === 'published' && (!$job['application_deadline'] || $job['application_deadline'] >= date('Y-m-d'));
$pageTitle = e($job['title']) . ' | FLEXJOB';
$pageStyles = ['job-detail', 'matching', 'rating'];
$backPath = is_role('admin') ? 'admin/jobs.php' : 'jobs.php';
$backLabel = is_role('admin') ? '← กลับไปตรวจสอบประกาศ' : '← กลับไปหน้าค้นหางาน';
require APP_ROOT . '/partials/header.php';
?>
<main class="job-detail-page py-4 py-lg-5">
    <div class="container">
        <a class="btn btn-link px-0 mb-3 text-decoration-none job-back-link" href="<?= BASE_URL ?>/<?= $backPath ?>"><?= $backLabel ?></a>
        <?php if (is_role('admin') && $job['status'] !== 'published'): ?><div class="alert alert-info mb-4">คุณกำลังดูประกาศสถานะ: <?= e($job['status']) ?></div><?php endif ?>

        <article class="card border-0 rounded-4 overflow-hidden shadow-sm job-detail-card">
            <?php if ($job['cover_image']): ?>
                <div class="job-cover"><img src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>" width="1600" height="700" fetchpriority="high" decoding="async"></div>
            <?php else: ?>
                <div class="job-cover job-cover-fallback" aria-hidden="true"><span><?= $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷') ?></span></div>
            <?php endif ?>

            <div class="row g-0">
                <div class="<?= is_role('worker') ? 'col-lg-8' : 'col-12' ?>">
                    <div class="job-main-content p-4 p-lg-5">
                        <div class="d-flex flex-wrap gap-2 mb-3"><?php if ($job['is_promoted']): ?><span class="badge rounded-pill text-bg-primary">✦ โปรโมต</span><?php endif ?><span class="badge rounded-pill text-bg-light border job-type-badge"><?= job_type($job['job_type']) ?></span><?php if ($job['work_interest_name']): ?><span class="badge rounded-pill job-interest-badge"><?= e($job['work_interest_name']) ?></span><?php endif ?></div>
                        <h1 class="display-6 fw-semibold mb-3"><?= e($job['title']) ?></h1>
                        <div class="d-flex flex-wrap align-items-center gap-2 job-company-row">
                            <?php if ($job['company_logo']): ?><img class="job-company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>" loading="lazy" decoding="async"><?php else: ?><span class="job-company-mark" aria-hidden="true">⌂</span><?php endif ?>
                            <span><?= e($job['company_name']) ?></span><?php if ($job['is_verified']): ?><span class="job-verified">✓ ผู้ว่าจ้างยืนยันแล้ว</span><?php endif ?>
                        </div>
                        <div class="mt-2"><?php $ratingSummary = ['average' => $job['employer_rating_average'], 'count' => $job['employer_rating_count']]; require APP_ROOT . '/partials/rating-summary.php'; ?></div>

                        <?php if ($jobMatch && $jobMatch['score'] !== null): ?>
                            <section class="job-match-card mt-4" aria-label="ความตรงกันกับโปรไฟล์"><div class="job-match-score" aria-label="ตรงกัน <?= $jobMatch['score'] ?> เปอร์เซ็นต์"><?= $jobMatch['score'] ?>%</div><div><b>Match กับโปรไฟล์ของคุณ</b><p><?= e(implode(' · ', $jobMatch['reasons'])) ?></p><?php if ($jobMatch['missing_required']): ?><small>ทักษะที่ยังไม่ระบุ: <?= e(implode(', ', $jobMatch['missing_required'])) ?></small><?php endif ?></div></section>
                        <?php endif ?>

                        <section class="row row-cols-1 row-cols-sm-2 g-0 job-facts mt-4" aria-label="ข้อมูลสำคัญของงาน"><div class="col"><div><span>ค่าจ้าง</span><strong><?= pay_text($job) ?></strong></div></div><div class="col"><div><span>สถานที่ทำงาน</span><strong><?= e($job['location'] ?: 'ไม่ระบุ') ?></strong></div></div><div class="col"><div><span>วัน / เวลาทำงาน</span><strong><?= e($job['work_date'] ?: 'ไม่ระบุ') ?></strong></div></div><div class="col"><div><span>เปิดรับ</span><strong><?= e((string) $job['positions']) ?> คน</strong></div></div></section>

                        <section class="job-section mt-5"><h2 class="h4">รายละเอียดงาน</h2><p><?= nl2br(e($job['description'])) ?></p></section>

                        <?php if ($jobRequirements['required_skills'] || $jobRequirements['preferred_skills']): ?>
                            <section class="job-section"><h2 class="h4">ทักษะที่ต้องการ</h2><div class="d-flex flex-wrap gap-2"><?php foreach ($jobRequirements['required_skills'] as $skill): ?><span class="skill-tag required"><?= e($skill) ?> <small>จำเป็น</small></span><?php endforeach ?><?php foreach ($jobRequirements['preferred_skills'] as $skill): ?><span class="skill-tag"><?= e($skill) ?> <small>เสริม</small></span><?php endforeach ?></div></section>
                        <?php endif ?>

                        <section class="job-section mb-0"><h2 class="h4">เกี่ยวกับผู้ว่าจ้าง</h2><p><?= e($job['company_description'] ?: 'ผู้ว่าจ้างผ่านการยืนยันตัวตนกับ FLEXJOB แล้ว') ?></p></section>
                    </div>
                </div>

                <?php if (is_role('worker')): ?>
                    <aside class="col-lg-4 job-application-aside"><section class="job-apply-card p-4 p-lg-4"><p class="eyebrow mb-2">APPLICATION</p><h2 class="h4 mb-2">สนใจงานนี้?</h2><p class="text-secondary small mb-4">ส่งใบสมัครของคุณให้ผู้ว่าจ้างได้ในไม่กี่ขั้นตอน</p>
                        <?php if (!$isOpen): ?>
                            <button class="btn btn-secondary w-100 py-2" disabled>ปิดรับสมัครแล้ว</button>
                        <?php elseif ($applicationStatus && $applicationStatus !== 'withdrawn'): ?>
                            <button class="btn btn-secondary w-100 py-2" disabled>คุณสมัครงานนี้แล้ว</button>
                        <?php else: ?>
                            <?php if ($applicationStatus === 'withdrawn'): ?><div class="alert alert-info small py-2">คุณถอนใบสมัครนี้แล้ว และสามารถสมัครใหม่ได้</div><?php endif ?>
                            <form action="<?= BASE_URL ?>/apply.php" method="post"><?= csrf_field() ?><input type="hidden" name="job_id" value="<?= $job['id'] ?>"><label class="form-label fw-semibold" for="cover_note">ข้อความถึงผู้ว่าจ้าง <span class="text-secondary fw-normal">(ไม่บังคับ)</span></label><textarea id="cover_note" class="form-control" name="cover_note" rows="5" placeholder="แนะนำตัวสั้น ๆ หรือบอกเหตุผลที่สนใจงานนี้"></textarea><button class="btn btn-primary w-100 py-2 mt-3" type="submit"><?= $applicationStatus === 'withdrawn' ? 'สมัครงานนี้อีกครั้ง' : 'สมัครงานนี้' ?></button></form>
                        <?php endif ?>
                        <p class="form-text mt-3 mb-0">FLEXJOB เป็นพื้นที่ให้ทั้งสองฝ่ายติดต่อและตกลงรายละเอียดงานร่วมกัน</p>
                    </section></aside>
                <?php endif ?>
            </div>
        </article>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
