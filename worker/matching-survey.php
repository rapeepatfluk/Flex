<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker', true);

$pdo = db();
$workerId = (int) user()['id'];
$error = null;
$workInterests = matching_work_interests($pdo);
$interestDescriptions = [
    'web-development' => 'เว็บไซต์ ระบบหลังบ้าน และงานเขียนโปรแกรม',
    'graphic-design' => 'โปสเตอร์ แบนเนอร์ และสื่อประชาสัมพันธ์',
    'ux-ui-design' => 'ออกแบบหน้าจอและประสบการณ์ใช้งาน',
    'video-editing' => 'ตัดต่อคลิปสั้น วิดีโอ และใส่เสียง',
    'photo-video' => 'ถ่ายภาพสินค้า บุคคล และงานกิจกรรม',
    'admin-document' => 'จัดเอกสาร คีย์ข้อมูล และงานสำนักงาน',
    'event-staff' => 'ลงทะเบียน ยืนบูธ และดูแลงานกิจกรรม',
    'sales-promotion' => 'ขายสินค้า แนะนำสินค้า และประชาสัมพันธ์',
    'food-service' => 'ร้านอาหาร คาเฟ่ เสิร์ฟ และบริการลูกค้า',
    'content-social' => 'เขียนคอนเทนต์และดูแลช่องทางออนไลน์',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $allowedCategories = ['part_time', 'event', 'freelance'];
        $preferences = array_values(array_intersect($allowedCategories, array_map('strval', (array) ($_POST['job_preferences'] ?? []))));
        $selectedWorkInterestIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['work_interests'] ?? [])), fn(int $id): bool => $id > 0)));
        $selectedSkillInputIds = (array) ($_POST['skill_ids'] ?? []);
        $customSkillsInput = trim((string) ($_POST['custom_skills'] ?? ''));
        $workMode = (string) ($_POST['preferred_work_mode'] ?? '');
        $availableFrom = trim((string) ($_POST['available_from'] ?? ''));
        // A missing checkbox is the private option. Unknown values also fail closed.
        $visibility = ($_POST['profile_visibility'] ?? '') === 'searchable' ? 'searchable' : 'application_only';

        if (!$selectedWorkInterestIds) throw new RuntimeException('กรุณาเลือกงานที่สนใจอย่างน้อย 1 หมวด');
        if (count($selectedWorkInterestIds) > 5) throw new RuntimeException('เลือกงานที่สนใจได้ไม่เกิน 5 หมวด');
        if (!$preferences) throw new RuntimeException('กรุณาเลือกรูปแบบการจ้างที่สนใจอย่างน้อย 1 ประเภท');
        if (!$selectedSkillInputIds && !matching_parse_skills($customSkillsInput)) throw new RuntimeException('กรุณาเลือกหรือระบุความสามารถอย่างน้อย 1 รายการ');
        if (!in_array($workMode, ['any', 'onsite', 'remote', 'hybrid'], true)) throw new RuntimeException('กรุณาเลือกรูปแบบงานที่ต้องการ');
        if ($availableFrom !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $availableFrom);
            if (!$date || $date->format('Y-m-d') !== $availableFrom) throw new RuntimeException('วันที่พร้อมเริ่มงานไม่ถูกต้อง');
        }

        $pdo->beginTransaction();
        matching_sync_worker_skill_selection($pdo, $workerId, $selectedSkillInputIds, $customSkillsInput);
        $pdo->prepare('INSERT INTO worker_profiles (user_id,work_province,preferred_work_mode,available_from,profile_visibility) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE work_province=VALUES(work_province),preferred_work_mode=VALUES(preferred_work_mode),available_from=VALUES(available_from),profile_visibility=VALUES(profile_visibility)')
            ->execute([$workerId, FLEXJOB_PROVINCE, $workMode, $availableFrom ?: null, $visibility]);
        matching_sync_worker_preferences($pdo, $workerId, $preferences);
        matching_sync_worker_work_interests($pdo, $workerId, $selectedWorkInterestIds);
        $pdo->prepare('UPDATE worker_profiles SET matching_survey_completed_at=COALESCE(matching_survey_completed_at,NOW()) WHERE user_id=?')
            ->execute([$workerId]);
        $pdo->commit();

        flash('success', 'บันทึกแบบสำรวจแล้ว งานแนะนำได้รับการปรับให้ตรงกับคุณมากขึ้น');
        redirect('worker/matching-results.php');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $exception->getMessage();
    }
}

$profileStatement = $pdo->prepare('SELECT preferred_work_mode,available_from,profile_visibility FROM worker_profiles WHERE user_id=?');
$profileStatement->execute([$workerId]);
$profile = $profileStatement->fetch() ?: [];
$selectedSkillStatement = $pdo->prepare('SELECT skill_id FROM worker_skills WHERE worker_user_id=? ORDER BY skill_id');
$selectedSkillStatement->execute([$workerId]);
$selectedSkillIds = array_map('intval', $selectedSkillStatement->fetchAll(PDO::FETCH_COLUMN));
$skillCategories = matching_skill_catalog($pdo, $selectedSkillIds);
require_once APP_ROOT . '/partials/skill-selector.php';
$preferenceStatement = $pdo->prepare('SELECT jc.category_slug FROM worker_job_preferences wjp JOIN job_categories jc ON jc.job_category_id=wjp.job_category_id WHERE wjp.worker_user_id=?');
$preferenceStatement->execute([$workerId]);
$selectedPreferences = $preferenceStatement->fetchAll(PDO::FETCH_COLUMN);
$workInterestStatement = $pdo->prepare('SELECT work_interest_id FROM worker_work_interests WHERE worker_user_id=? ORDER BY work_interest_id');
$workInterestStatement->execute([$workerId]);
$selectedWorkInterestIds = array_map('intval', $workInterestStatement->fetchAll(PDO::FETCH_COLUMN));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) {
    $profile['preferred_work_mode'] = (string) ($_POST['preferred_work_mode'] ?? 'any');
    $profile['available_from'] = trim((string) ($_POST['available_from'] ?? ''));
    $profile['profile_visibility'] = (string) ($_POST['profile_visibility'] ?? 'application_only');
    $selectedPreferences = array_map('strval', (array) ($_POST['job_preferences'] ?? []));
    $selectedWorkInterestIds = array_map('intval', (array) ($_POST['work_interests'] ?? []));
}

$pageTitle = 'แบบสำรวจ Matching | FLEXJOB';
$pageStyles = ['matching', 'matching-survey', 'skill-selector'];
$pageScripts = ['matching-survey'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container-fluid px-lg-5 py-5 survey-page">
    <div class="row justify-content-center"><div class="col-xl-11 col-xxl-10">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
            <div><p class="eyebrow mb-2">JOB MATCHING SURVEY</p><h1 class="h2 mb-2">บอกเราว่างานแบบไหนเหมาะกับคุณ</h1><p class="text-secondary mb-0">ใช้เวลาประมาณ 1–2 นาที คำตอบแก้ไขภายหลังได้ และใช้แนะนำงานในจังหวัด<?= e(FLEXJOB_PROVINCE) ?>เท่านั้น</p></div>
        </div>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif ?>
        <div class="survey-progress mb-3" aria-live="polite"><div class="d-flex justify-content-between small mb-2"><b id="surveyStepLabel">ขั้นตอน 1 จาก 5</b><span id="surveyPercent">20%</span></div><div class="progress"><div class="progress-bar bg-success" id="surveyProgressBar" style="width:20%"></div></div></div>
        <form method="post" class="card border-0 shadow-sm" id="matchingSurvey" novalidate>
            <?= csrf_field() ?>
            <div class="card-body p-4 p-md-5">
                <section class="survey-step" data-survey-step="0">
                    <p class="eyebrow">STEP 1</p><h2 class="h4">คุณสนใจทำงานด้านไหน?</h2><p class="text-secondary">เลือกได้ 1–5 หมวด นี่คือสิ่งที่คุณอยากทำ ไม่จำเป็นต้องเป็นความสามารถที่ถนัดอยู่แล้ว</p>
                    <div class="survey-options mt-4"><?php foreach ($workInterests as $interest): ?><label class="survey-option"><input class="form-check-input" type="checkbox" name="work_interests[]" value="<?= $interest['work_interest_id'] ?>" <?= in_array((int) $interest['work_interest_id'], $selectedWorkInterestIds, true) ? 'checked' : '' ?>><span><b><?= e($interest['interest_name']) ?></b><small><?= e($interestDescriptions[$interest['interest_slug']] ?? '') ?></small></span></label><?php endforeach ?></div>
                    <div class="invalid-feedback d-block" id="workInterestError" hidden>กรุณาเลือก 1–5 หมวด</div>
                </section>
                <section class="survey-step" data-survey-step="1">
                    <p class="eyebrow">STEP 2</p><h2 class="h4">คุณสนใจรูปแบบการจ้างประเภทใด?</h2><p class="text-secondary">เลือกได้มากกว่า 1 ประเภท เช่น สนใจทั้งพาร์ทไทม์และฟรีแลนซ์</p>
                    <div class="survey-options mt-4"><?php foreach (['part_time' => ['พาร์ทไทม์', 'งานเป็นกะหรือเป็นช่วงเวลา'], 'event' => ['งานอีเวนต์', 'งานรายวันและกิจกรรมในพื้นที่'], 'freelance' => ['ฟรีแลนซ์', 'งานเป็นชิ้นหรือเป็นโปรเจกต์']] as $value => [$label, $description]): ?><label class="survey-option"><input class="form-check-input" type="checkbox" name="job_preferences[]" value="<?= $value ?>" <?= in_array($value, $selectedPreferences, true) ? 'checked' : '' ?>><span><b><?= $label ?></b><small><?= $description ?></small></span></label><?php endforeach ?></div>
                    <div class="invalid-feedback d-block" id="preferenceError" hidden>กรุณาเลือกอย่างน้อย 1 ประเภท</div>
                </section>
                <section class="survey-step" data-survey-step="2">
                    <p class="eyebrow">STEP 3</p><h2 class="h4">คุณมีความสามารถด้านใดบ้าง?</h2><p class="text-secondary">เลือกความสามารถภาพรวมที่ทำได้จริง เช่น งานขาย ออกแบบกราฟิก หรือพัฒนาเว็บไซต์</p>
                    <?php render_skill_selector('surveySkills', 'ความสามารถของคุณ', 'เลือกคำกว้างที่สะท้อนงานที่คุณทำได้ ระบบจะใช้แนะนำงานที่เหมาะกับคุณ', $skillCategories, $selectedSkillIds, 'skill_ids[]', 'custom_skills', true); ?>
                    <div class="invalid-feedback d-block" id="surveySkillError" role="alert" hidden>กรุณาเลือกหรือระบุความสามารถอย่างน้อย 1 รายการ</div>
                </section>
                <section class="survey-step" data-survey-step="3">
                    <p class="eyebrow">STEP 4</p><h2 class="h4">คุณต้องการทำงานรูปแบบใด?</h2><p class="text-secondary">ทุกงานอยู่ในขอบเขตจังหวัด<?= e(FLEXJOB_PROVINCE) ?> ส่วน “ออนไลน์” หมายถึงทำงานจากที่ใดก็ได้โดยไม่ต้องเข้าสถานที่ทำงาน</p>
                    <div class="survey-options mt-4"><?php foreach (['any' => ['ได้ทุกรูปแบบ', 'พร้อมพิจารณาทั้งงานที่สถานที่ ออนไลน์ และ Hybrid'], 'onsite' => ['ทำงานที่สถานที่', 'เดินทางไปยังสถานที่ทำงานในบุรีรัมย์'], 'remote' => ['ทำงานออนไลน์', 'ทำงานผ่านอินเทอร์เน็ต ไม่ต้องเข้าสถานที่'], 'hybrid' => ['Hybrid', 'สลับระหว่างออนไลน์และเข้าสถานที่']] as $value => [$label, $description]): ?><label class="survey-option"><input class="form-check-input" type="radio" name="preferred_work_mode" value="<?= $value ?>" <?= ($profile['preferred_work_mode'] ?? 'any') === $value ? 'checked' : '' ?> required><span><b><?= $label ?></b><small><?= $description ?></small></span></label><?php endforeach ?></div>
                </section>
                <section class="survey-step" data-survey-step="4">
                    <p class="eyebrow">STEP 5</p><h2 class="h4">คุณพร้อมเริ่มงานเมื่อไร?</h2><p class="text-secondary">ข้อมูลนี้แสดงให้ผู้ว่าจ้างประกอบการพิจารณา แต่ยังไม่ใช้คำนวณคะแนน Matching</p>
                    <label class="form-label mt-3" for="availableFrom">พร้อมเริ่มงานตั้งแต่ <span class="text-secondary">(ไม่บังคับ)</span></label><input class="form-control" id="availableFrom" type="date" name="available_from" value="<?= e($profile['available_from'] ?? '') ?>">
                    <fieldset class="mt-4"><legend class="h6">การแสดงโปรไฟล์ต่อนายจ้าง</legend><div class="border rounded p-3 mt-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="profile_visibility" value="searchable" id="profileVisibility" <?= ($profile['profile_visibility'] ?? 'application_only') === 'searchable' ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="profileVisibility">ให้นายจ้างใน FLEXJOB ค้นพบโปรไฟล์ของฉัน</label></div><p class="small text-secondary mt-2 mb-0">เมื่อเปิดใช้งาน เฉพาะนายจ้างที่ผ่านการยืนยันและมีประกาศงานที่เปิดรับสมัครเท่านั้นที่ค้นหาคุณ ส่งคำเชิญ และดู Resume หรือ Portfolio ได้ ระบบยังซ่อนอีเมลและเบอร์โทรไว้</p></div><p class="small text-secondary mt-2 mb-0">หากไม่เปิด คุณยังค้นหาและสมัครงานเองได้ตามปกติ และนายจ้างจะเห็นข้อมูลติดต่อเมื่อคุณสมัครงานของเขาแล้ว</p></fieldset>
                </section>
                <div class="survey-actions d-flex flex-column-reverse flex-sm-row justify-content-between gap-2 mt-5"><button class="btn btn-outline-secondary" id="surveyPrevious" type="button">ย้อนกลับ</button><button class="btn btn-success px-4" id="surveyNext" type="button">ถัดไป</button><button class="btn btn-success px-4" id="surveySubmit" type="submit">บันทึกและดูงานแนะนำ</button></div>
            </div>
        </form>
    </div></div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
