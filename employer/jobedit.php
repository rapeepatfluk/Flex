<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$jobId = (int) ($_GET['id'] ?? $_POST['job_id'] ?? 0);
$jobStmt = $pdo->prepare("SELECT j.job_id,j.work_interest_id,j.job_title,j.job_description,j.work_location,j.work_province,j.work_schedule,j.work_start_date,j.work_end_date,j.work_start_time,j.work_end_time,j.work_mode,j.application_deadline,j.pay_amount,j.pay_unit,j.open_positions,jc.category_slug,
    GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_name SEPARATOR ', ') required_skills,
    GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_name SEPARATOR ', ') preferred_skills
    FROM jobs j JOIN job_categories jc ON jc.job_category_id=j.job_category_id
    LEFT JOIN job_skills js ON js.job_id=j.job_id LEFT JOIN skills s ON s.skill_id=js.skill_id
    WHERE j.job_id=? AND j.employer_user_id=? GROUP BY j.job_id");
$jobStmt->execute([$jobId, user()['id']]);
$job = $jobStmt->fetch();
$workInterests = matching_work_interests($pdo);

if (!$job) {
    flash('error', 'ไม่พบประกาศงานที่ต้องการแก้ไข');
    redirect('employer/dashboard.php');
}

$requiredSkillStmt = $pdo->prepare("SELECT skill_id FROM job_skills WHERE job_id=? AND importance='required' ORDER BY skill_id");
$requiredSkillStmt->execute([$jobId]);
$selectedRequiredSkillIds = array_map('intval', $requiredSkillStmt->fetchAll(PDO::FETCH_COLUMN));
$preferredSkillStmt = $pdo->prepare("SELECT skill_id FROM job_skills WHERE job_id=? AND importance='preferred' ORDER BY skill_id");
$preferredSkillStmt->execute([$jobId]);
$selectedPreferredSkillIds = array_map('intval', $preferredSkillStmt->fetchAll(PDO::FETCH_COLUMN));
$skillCategories = matching_skill_catalog($pdo, array_merge($selectedRequiredSkillIds, $selectedPreferredSkillIds));
require_once APP_ROOT . '/partials/skill-selector.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $categoryStmt = $pdo->prepare('SELECT job_category_id FROM job_categories WHERE category_slug=?');
        $categoryStmt->execute([$_POST['job_type'] ?? '']);
        $categoryId = $categoryStmt->fetchColumn();

        if (!$categoryId) {
            throw new RuntimeException('ประเภทงานไม่ถูกต้อง');
        }
        $workMode = $_POST['work_mode'] ?? 'onsite';
        if (!in_array($workMode, ['onsite', 'remote', 'hybrid'], true)) throw new RuntimeException('รูปแบบงานไม่ถูกต้อง');
        $workInterestId = (int) ($_POST['work_interest_id'] ?? 0);
        if (!matching_work_interest_exists($pdo, $workInterestId)) throw new RuntimeException('กรุณาเลือกหมวดงานหลัก');
        $schedule = job_schedule_from_input($_POST, (string) ($job['work_schedule'] ?? ''));

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE jobs SET job_category_id=?, work_interest_id=?, job_title=?, job_description=?, work_location=?, work_province=?, work_schedule=?, work_start_date=?, work_end_date=?, work_start_time=?, work_end_time=?, work_mode=?, application_deadline=?, pay_amount=?, pay_unit=?, open_positions=? WHERE job_id=? AND employer_user_id=?')
            ->execute([
                $categoryId,
                $workInterestId,
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? ''),
                trim($_POST['address'] ?? ''),
                FLEXJOB_PROVINCE,
                $schedule['summary'],
                $schedule['start_date'],
                $schedule['end_date'],
                $schedule['start_time'],
                $schedule['end_time'],
                $workMode,
                ($_POST['application_deadline'] ?? '') ?: null,
                (float) ($_POST['pay_amount'] ?? 0),
                $_POST['pay_unit'] ?? 'hour',
                max(1, (int) ($_POST['positions'] ?? 1)),
                $jobId,
                user()['id'],
            ]);
        matching_sync_job_skill_selection($pdo, $jobId, (array) ($_POST['required_skill_ids'] ?? []), trim((string) ($_POST['custom_required_skills'] ?? '')), (array) ($_POST['preferred_skill_ids'] ?? []), trim((string) ($_POST['custom_preferred_skills'] ?? '')));

        $image = upload_file('job_image', ['jpg', 'jpeg', 'png', 'webp'], 'jobs');
        if ($image) {
            $imageStmt = $pdo->prepare('SELECT job_image_id FROM job_images WHERE job_id=? ORDER BY display_order, job_image_id LIMIT 1');
            $imageStmt->execute([$jobId]);
            $jobImageId = $imageStmt->fetchColumn();

            if ($jobImageId) {
                $pdo->prepare('UPDATE job_images SET image_file_path=? WHERE job_image_id=?')->execute([$image, $jobImageId]);
            } else {
                $pdo->prepare('INSERT INTO job_images (job_id, image_file_path) VALUES (?, ?)')->execute([$jobId, $image]);
            }
        }
        $pdo->commit();

        flash('success', 'บันทึกการแก้ไขประกาศงานแล้ว');
        redirect('employer/dashboard.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }

    $jobStmt->execute([$jobId, user()['id']]);
    $job = $jobStmt->fetch();
}

$pageTitle = 'แก้ไขประกาศงาน | FLEXJOB';
$pageStyles = ['skill-selector'];
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <p class="eyebrow">EDIT JOB POST</p>
            <h1 class="h2 mb-1">แก้ไขประกาศงาน</h1>
            <p class="text-secondary mb-0">ปรับรายละเอียดงาน ค่าจ้าง สถานที่ และรูปประกอบได้</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/employer/dashboard.php">กลับไปจัดการประกาศ</a>
    </div>

    <?php if (($job['work_province'] ?? '') !== FLEXJOB_PROVINCE): ?><div class="alert alert-warning">ประกาศเดิมนี้อยู่นอกขอบเขตจังหวัดบุรีรัมย์ จึงไม่แสดงในหน้าค้นหาสาธารณะ กรุณาตรวจสอบสถานที่ทำงานก่อนบันทึก ระบบจะกำหนดจังหวัดเป็นบุรีรัมย์เมื่อคุณบันทึกการแก้ไข</div><?php endif ?>
    <form class="card border-0 shadow-sm" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="job_id" value="<?= $jobId ?>">
        <div class="card-body p-4 p-md-5">
            <div class="row g-3">
                <div class="col-12"><label class="form-label" for="title">ชื่องาน</label><input id="title" class="form-control" name="title" value="<?= e($job['job_title']) ?>" required></div>
                <div class="col-md-6"><label class="form-label" for="job_type">ประเภทงาน</label><select id="job_type" class="form-select" name="job_type"><?php foreach (['part_time' => 'พาร์ทไทม์', 'event' => 'งานอีเวนต์', 'freelance' => 'ฟรีแลนซ์'] as $value => $label): ?><option value="<?= $value ?>" <?= $job['category_slug'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="work_interest_id">งานนี้อยู่ในหมวดใด?</label><select id="work_interest_id" class="form-select" name="work_interest_id" required><option value="">เลือกหมวดงานหลัก</option><?php foreach ($workInterests as $interest): ?><option value="<?= $interest['work_interest_id'] ?>" <?= (int) ($job['work_interest_id'] ?? 0) === (int) $interest['work_interest_id'] ? 'selected' : '' ?>><?= e($interest['interest_name']) ?></option><?php endforeach ?></select><div class="form-text">ประกาศเดิมที่ยังไม่มีหมวดต้องเลือกก่อนบันทึก</div></div>
                <div class="col-md-6"><label class="form-label" for="positions">จำนวนคน</label><input id="positions" class="form-control" type="number" name="positions" min="1" value="<?= e((string) $job['open_positions']) ?>" required></div>
                <div class="col-12"><label class="form-label" for="description">รายละเอียดงาน</label><textarea id="description" class="form-control" name="description" rows="5" required><?= e($job['job_description']) ?></textarea></div>                <div class="col-12">
                    <?php render_skill_selector('requiredSkills', 'ความสามารถที่จำเป็น', 'ผู้สมัครควรมีความสามารถภาพรวมเหล่านี้ ระบบใช้เป็นปัจจัยหลักในการจับคู่', $skillCategories, $selectedRequiredSkillIds, 'required_skill_ids[]', 'custom_required_skills'); ?>
                </div>
                <div class="col-12">
                    <?php render_skill_selector('preferredSkills', 'ความสามารถเพิ่มเติม', 'มีแล้วได้เปรียบ แต่ไม่ใช้ตัดสิทธิ์ผู้สมัคร', $skillCategories, $selectedPreferredSkillIds, 'preferred_skill_ids[]', 'custom_preferred_skills'); ?>
                </div>
                <div class="col-md-6"><label class="form-label">จังหวัด</label><input class="form-control" value="<?= e(FLEXJOB_PROVINCE) ?>" disabled><div class="form-text">FLEXJOB เปิดรับเฉพาะงานในจังหวัดบุรีรัมย์</div></div>
                <div class="col-md-6"><label class="form-label" for="address">สถานที่ทำงาน / จุดประสานงานในบุรีรัมย์</label><input id="address" class="form-control" name="address" value="<?= e($job['work_location']) ?>" required><div class="form-text">งานออนไลน์ให้ระบุอำเภอหรือจุดประสานงานของผู้ว่าจ้างในบุรีรัมย์</div></div>
                <div class="col-12"><fieldset class="border rounded-4 p-3"><legend class="h6 mb-2">วันและเวลาทำงาน <span class="text-secondary fw-normal">(ไม่บังคับ)</span></legend><p class="form-text mt-0 mb-3" id="editWorkScheduleHelp">แยกข้อมูลวันและเวลาเพื่อให้อ่านง่ายและนำไปใช้ค้นหางานได้ในอนาคต</p><div class="row g-3"><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_start_date">วันเริ่มงาน</label><input id="work_start_date" class="form-control" type="date" name="work_start_date" value="<?= e($job['work_start_date'] ?? '') ?>" aria-describedby="editWorkScheduleHelp"></div><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_end_date">วันสิ้นสุดงาน</label><input id="work_end_date" class="form-control" type="date" name="work_end_date" value="<?= e($job['work_end_date'] ?? '') ?>" aria-describedby="editWorkScheduleHelp"></div><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_start_time">เวลาเริ่มงาน</label><input id="work_start_time" class="form-control" type="time" name="work_start_time" value="<?= e(isset($job['work_start_time']) ? substr((string) $job['work_start_time'], 0, 5) : '') ?>" aria-describedby="editWorkScheduleHelp"></div><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_end_time">เวลาสิ้นสุดงาน</label><input id="work_end_time" class="form-control" type="time" name="work_end_time" value="<?= e(isset($job['work_end_time']) ? substr((string) $job['work_end_time'], 0, 5) : '') ?>" aria-describedby="editWorkScheduleHelp"></div></div><?php if (empty($job['work_start_date']) && !empty($job['work_schedule'])): ?><p class="form-text mb-0 mt-3">กำหนดการเดิม: <?= e($job['work_schedule']) ?> — ระบุข้อมูลด้านบนแล้วบันทึกเพื่อเปลี่ยนเป็นรูปแบบใหม่</p><?php endif; ?></fieldset></div>
                <div class="col-md-6"><label class="form-label" for="work_mode">รูปแบบการทำงาน</label><select id="work_mode" class="form-select" name="work_mode"><?php foreach (['onsite' => 'ทำงานที่สถานที่', 'remote' => 'ทำงานออนไลน์', 'hybrid' => 'Hybrid'] as $value => $label): ?><option value="<?= $value ?>" <?= ($job['work_mode'] ?? 'onsite') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
                <div class="col-md-6"><label class="form-label" for="application_deadline">วันปิดรับสมัคร</label><input id="application_deadline" class="form-control" type="date" name="application_deadline" value="<?= e($job['application_deadline'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label" for="pay_amount">ค่าจ้าง</label><input id="pay_amount" class="form-control" type="number" name="pay_amount" min="1" value="<?= e((string) $job['pay_amount']) ?>" required></div>
                <div class="col-md-6"><label class="form-label" for="pay_unit">หน่วย</label><select id="pay_unit" class="form-select" name="pay_unit"><?php foreach (['hour' => 'ต่อชั่วโมง', 'day' => 'ต่อวัน', 'project' => 'ต่อโปรเจกต์'] as $value => $label): ?><option value="<?= $value ?>" <?= $job['pay_unit'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="job_image">เปลี่ยนรูปประกอบ</label><input id="job_image" class="form-control" type="file" name="job_image" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">ไม่เลือกไฟล์หากต้องการใช้รูปเดิม</div></div>
            </div>
            <div class="d-flex justify-content-end mt-4"><button class="btn btn-success px-4" type="submit">บันทึกการแก้ไข</button></div>
        </div>
    </form>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
