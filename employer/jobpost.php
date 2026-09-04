<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$verificationStmt = $pdo->prepare("SELECT COALESCE((SELECT ed.document_status FROM employer_documents ed WHERE ed.employer_user_id=ep.user_id ORDER BY ed.submitted_at DESC LIMIT 1), 'not_submitted') FROM employer_profiles ep WHERE ep.user_id=?");
$verificationStmt->execute([user()['id']]);
$verificationStatus = $verificationStmt->fetchColumn() ?: 'not_submitted';
$workInterests = matching_work_interests($pdo);
$skillCategories = matching_skill_catalog($pdo);
require_once APP_ROOT . '/partials/skill-selector.php';
$formData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if ($verificationStatus !== 'approved') throw new RuntimeException('บัญชีของคุณยังไม่ผ่านการยืนยัน จึงยังโพสต์งานไม่ได้');
        $workMode = $_POST['work_mode'] ?? 'onsite';
        if (!in_array($workMode, ['onsite', 'remote', 'hybrid'], true)) throw new RuntimeException('รูปแบบการทำงานไม่ถูกต้อง');
        if (trim($_POST['title'] ?? '') === '' || trim($_POST['description'] ?? '') === '') throw new RuntimeException('กรุณากรอกชื่องานและรายละเอียดงาน');

        $categoryStatement = $pdo->prepare('SELECT job_category_id FROM job_categories WHERE category_slug=?');
        $categoryStatement->execute([$_POST['job_type'] ?? '']);
        $categoryId = (int) $categoryStatement->fetchColumn();
        if (!$categoryId) throw new RuntimeException('ประเภทงานไม่ถูกต้อง');
        $workInterestId = (int) ($_POST['work_interest_id'] ?? 0);
        if (!matching_work_interest_exists($pdo, $workInterestId)) throw new RuntimeException('กรุณาเลือกหมวดงานหลัก');
        $schedule = job_schedule_from_input($_POST);

        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO jobs (employer_user_id,job_category_id,work_interest_id,job_title,job_description,work_location,work_province,work_schedule,work_start_date,work_end_date,work_start_time,work_end_time,work_mode,application_deadline,pay_amount,pay_unit,open_positions) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            user()['id'], $categoryId, $workInterestId, trim($_POST['title'] ?? ''), trim($_POST['description'] ?? ''),
            trim($_POST['address'] ?? ''), FLEXJOB_PROVINCE, $schedule['summary'], $schedule['start_date'], $schedule['end_date'], $schedule['start_time'], $schedule['end_time'], $workMode,
            ($_POST['application_deadline'] ?? '') ?: null, (float) ($_POST['pay_amount'] ?? 0),
            $_POST['pay_unit'] ?? 'hour', max(1, (int) ($_POST['positions'] ?? 1)),
        ]);
        $jobId = (int) $pdo->lastInsertId();
        if (!$jobId) throw new RuntimeException('ไม่สามารถสร้างประกาศงานได้');

        matching_sync_job_skill_assignments($pdo, $jobId, (array) ($_POST['job_skill_ids'] ?? []), [], trim((string) ($_POST['custom_job_skills'] ?? '')), 'required');
        $image = upload_file('job_image', ['jpg', 'jpeg', 'png', 'webp'], 'jobs');
        if ($image) $pdo->prepare('INSERT INTO job_images (job_id,image_file_path) VALUES (?,?)')->execute([$jobId, $image]);
        $pdo->commit();

        flash('success', 'โพสต์งานสำเร็จ ประกาศแสดงทันทีและจะถูกตรวจสอบย้อนหลังโดย Admin');
        redirect('employer/dashboard.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
}

$pageTitle = 'สร้างประกาศงาน | FLEXJOB';
$pageStyles = ['skill-selector', 'employer-jobpost'];
require APP_ROOT . '/partials/header.php';
?>

<main class="job-post-page">
    <div class="container py-4 py-lg-5">
        <header class="job-post-hero">
            <div><p class="eyebrow mb-2">CREATE JOB POST</p><h1 class="mb-2">สร้างประกาศงานให้คนที่ใช่เห็น</h1><p class="text-secondary mb-0">ระบุข้อมูลสำคัญให้ครบถ้วน เพื่อให้ระบบแนะนำผู้สมัครที่เหมาะกับงานของคุณได้ดีขึ้น</p></div>
            <a class="btn btn-outline-primary job-post-back-button" href="<?= BASE_URL ?>/employer/dashboard.php"><span aria-hidden="true">←</span> กลับไปจัดการประกาศ</a>
        </header>

        <?php if ($verificationStatus !== 'approved'): ?>
            <section class="alert alert-warning job-post-alert mb-0" role="alert"><strong>ยังไม่สามารถเผยแพร่ประกาศได้</strong><span>บัญชีผู้ว่าจ้างต้องผ่านการยืนยันเอกสารจาก Admin ก่อน</span></section>
        <?php else: ?>
            <section class="card border-0 shadow-sm job-post-guide mb-4">
                <div class="card-body p-4">
                    <div class="job-post-guide-heading"><p class="eyebrow mb-2">POSTING GUIDE</p><h2 class="h5 mb-1">ประกาศที่ดีควรมีอะไรบ้าง?</h2><p class="small text-secondary mb-0">เช็กข้อมูลสำคัญก่อนเริ่มกรอกฟอร์ม</p></div>
                    <ol class="job-post-tips mb-0">
                        <li><span>1</span><div><strong>บอกหน้าที่ให้ชัดเจน</strong><small>ผู้สมัครจะเข้าใจขอบเขตงานตั้งแต่แรก</small></div></li>
                        <li><span>2</span><div><strong>ระบุเวลาและค่าจ้าง</strong><small>ช่วยให้คนที่พร้อมจริงตัดสินใจเร็วขึ้น</small></div></li>
                        <li><span>3</span><div><strong>เลือกความสามารถภาพรวม</strong><small>เลือกเฉพาะสิ่งสำคัญต่อการทำงานจริง</small></div></li>
                    </ol>
                </div>
            </section>

            <form method="post" enctype="multipart/form-data" class="job-post-form">
                <?= csrf_field() ?>
                <section class="card border-0 shadow-sm job-post-section mb-4"><div class="card-body p-4 p-lg-5">
                    <div class="job-post-section-heading"><span class="job-post-step">01</span><div><h2 class="h4 mb-1">รายละเอียดประกาศ</h2><p class="mb-0 text-secondary">ข้อมูลพื้นฐานที่จะช่วยให้ผู้สมัครเข้าใจงานในทันที</p></div></div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label" for="title">ชื่องาน <span class="text-danger">*</span></label><input id="title" class="form-control" name="title" value="<?= e($formData['title'] ?? '') ?>" maxlength="160" placeholder="เช่น Event Staff งานเปิดตัวสินค้า" required></div>
                        <div class="col-md-6"><label class="form-label" for="job_type">ประเภทงาน <span class="text-danger">*</span></label><select id="job_type" class="form-select" name="job_type" required><?php foreach (['part_time'=>'พาร์ทไทม์','event'=>'งานอีเวนต์','freelance'=>'ฟรีแลนซ์'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($formData['job_type'] ?? 'part_time') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
                        <div class="col-md-6"><label class="form-label" for="work_interest_id">หมวดงานหลัก <span class="text-danger">*</span></label><select id="work_interest_id" class="form-select" name="work_interest_id" required><option value="">เลือกหมวดงานหลัก</option><?php foreach ($workInterests as $interest): ?><option value="<?= (int) $interest['work_interest_id'] ?>" <?= (int) ($formData['work_interest_id'] ?? 0) === (int) $interest['work_interest_id'] ? 'selected' : '' ?>><?= e($interest['interest_name']) ?></option><?php endforeach ?></select><div class="form-text">เลือกจากลักษณะงานหลัก ไม่ใช่ทักษะหรือรูปแบบการจ้าง</div></div>
                        <div class="col-md-4"><label class="form-label" for="positions">จำนวนที่รับ <span class="text-danger">*</span></label><input id="positions" class="form-control" type="number" name="positions" min="1" value="<?= e((string) ($formData['positions'] ?? 1)) ?>" required></div>
                        <div class="col-12"><label class="form-label" for="description">รายละเอียดงาน <span class="text-danger">*</span></label><textarea id="description" class="form-control" name="description" rows="6" maxlength="5000" placeholder="บอกหน้าที่ ความรับผิดชอบ และสิ่งที่ผู้สมัครควรทราบ" required><?= e($formData['description'] ?? '') ?></textarea></div>
                    </div>
                </div></section>

                <section class="card border-0 shadow-sm job-post-section mb-4"><div class="card-body p-4 p-lg-5">
                    <div class="job-post-section-heading"><span class="job-post-step">02</span><div><h2 class="h4 mb-1">สถานที่ เวลา และค่าจ้าง</h2><p class="mb-0 text-secondary">ข้อมูลที่ช่วยให้ผู้สมัครตัดสินใจได้อย่างมั่นใจ</p></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="work_mode">รูปแบบการทำงาน <span class="text-danger">*</span></label><select id="work_mode" class="form-select" name="work_mode"><?php foreach (['onsite'=>'ทำงานที่สถานที่','remote'=>'ทำงานออนไลน์','hybrid'=>'Hybrid'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($formData['work_mode'] ?? 'onsite') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
                        <div class="col-md-6"><label class="form-label" for="province">จังหวัด</label><input id="province" class="form-control" value="<?= e(FLEXJOB_PROVINCE) ?>" readonly><div class="form-text">FLEXJOB เปิดรับเฉพาะงานในจังหวัดบุรีรัมย์</div></div>
                        <div class="col-12"><label class="form-label" for="address">สถานที่ทำงาน / จุดประสานงาน <span class="text-danger">*</span></label><input id="address" class="form-control" name="address" value="<?= e($formData['address'] ?? '') ?>" maxlength="255" placeholder="เช่น อำเภอเมืองบุรีรัมย์ หรือชื่อสถานที่" required><div class="form-text">สำหรับงานออนไลน์ กรุณาระบุจุดประสานงานของผู้ว่าจ้างในบุรีรัมย์</div></div>
                        <div class="col-12"><fieldset class="border rounded-4 p-3 h-100"><legend class="h6 mb-2">วันและเวลาทำงาน <span class="text-secondary fw-normal">(ไม่บังคับ)</span></legend><p class="form-text mt-0 mb-3" id="workScheduleHelp">ระบุแยกกันเพื่อให้ประกาศอ่านง่ายและรองรับการค้นหาตามวันหรือเวลาในอนาคต</p><div class="row g-3"><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_start_date">วันเริ่มงาน</label><input id="work_start_date" class="form-control" type="date" name="work_start_date" value="<?= e($formData['work_start_date'] ?? '') ?>" aria-describedby="workScheduleHelp"></div><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_end_date">วันสิ้นสุดงาน</label><input id="work_end_date" class="form-control" type="date" name="work_end_date" value="<?= e($formData['work_end_date'] ?? '') ?>" aria-describedby="workScheduleHelp"></div><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_start_time">เวลาเริ่มงาน</label><input id="work_start_time" class="form-control" type="time" name="work_start_time" value="<?= e($formData['work_start_time'] ?? '') ?>" aria-describedby="workScheduleHelp"></div><div class="col-sm-6 col-xl-3"><label class="form-label" for="work_end_time">เวลาสิ้นสุดงาน</label><input id="work_end_time" class="form-control" type="time" name="work_end_time" value="<?= e($formData['work_end_time'] ?? '') ?>" aria-describedby="workScheduleHelp"></div></div></fieldset></div>
                        <div class="col-md-6"><label class="form-label" for="application_deadline">วันปิดรับสมัคร</label><input id="application_deadline" class="form-control" type="date" name="application_deadline" min="<?= date('Y-m-d') ?>" value="<?= e($formData['application_deadline'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="pay_amount">ค่าจ้าง (บาท) <span class="text-danger">*</span></label><input id="pay_amount" class="form-control" type="number" name="pay_amount" min="1" inputmode="numeric" value="<?= e((string) ($formData['pay_amount'] ?? '')) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="pay_unit">หน่วยค่าจ้าง <span class="text-danger">*</span></label><select id="pay_unit" class="form-select" name="pay_unit"><?php foreach (['hour'=>'ต่อชั่วโมง','day'=>'ต่อวัน','project'=>'ต่อโปรเจกต์'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($formData['pay_unit'] ?? 'hour') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
                    </div>
                </div></section>

                <section class="card border-0 shadow-sm job-post-section mb-4"><div class="card-body p-4 p-lg-5">
                    <div class="job-post-section-heading"><span class="job-post-step">03</span><div><h2 class="h4 mb-1">ความสามารถและรูปประกอบ</h2><p class="mb-0 text-secondary">เลือกความสามารถภาพรวมที่ผู้สมัครควรมีเพื่อให้ระบบจับคู่ได้แม่นยำ</p></div></div>
                    <?php render_job_skill_assignment_selector('jobSkills', $skillCategories); ?>
                    <div class="job-post-upload mt-4"><div><label class="form-label mb-1" for="job_image">รูปประกอบประกาศ</label><p class="form-text mb-0">เพิ่มรูปเพื่อให้ประกาศโดดเด่นขึ้น รองรับ JPG, PNG หรือ WEBP</p></div><input id="job_image" class="form-control" type="file" name="job_image" accept=".jpg,.jpeg,.png,.webp"></div>
                </div></section>

                <div class="job-post-submit-bar"><p class="small text-secondary mb-0">เมื่อเผยแพร่แล้ว ประกาศจะแสดงทันทีและจะถูกตรวจสอบย้อนหลังโดย Admin</p><button class="btn btn-primary job-post-submit-button" type="submit">เผยแพร่ประกาศ <span aria-hidden="true">→</span></button></div>
            </form>
        <?php endif ?>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
