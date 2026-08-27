<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

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
        $skillsInput = trim((string) ($_POST['skills'] ?? ''));
        $workMode = (string) ($_POST['preferred_work_mode'] ?? '');
        $availableFrom = trim((string) ($_POST['available_from'] ?? ''));
        // A missing checkbox is the private option. Unknown values also fail closed.
        $visibility = ($_POST['profile_visibility'] ?? '') === 'searchable' ? 'searchable' : 'application_only';

        if (!$selectedWorkInterestIds) throw new RuntimeException('กรุณาเลือกงานที่สนใจอย่างน้อย 1 หมวด');
        if (count($selectedWorkInterestIds) > 5) throw new RuntimeException('เลือกงานที่สนใจได้ไม่เกิน 5 หมวด');
        if (!$preferences) throw new RuntimeException('กรุณาเลือกรูปแบบการจ้างที่สนใจอย่างน้อย 1 ประเภท');
        if (!matching_parse_skills($skillsInput)) throw new RuntimeException('กรุณาระบุทักษะอย่างน้อย 1 รายการ');
        if (!in_array($workMode, ['any', 'onsite', 'remote', 'hybrid'], true)) throw new RuntimeException('กรุณาเลือกรูปแบบงานที่ต้องการ');
        if ($availableFrom !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $availableFrom);
            if (!$date || $date->format('Y-m-d') !== $availableFrom) throw new RuntimeException('วันที่พร้อมเริ่มงานไม่ถูกต้อง');
        }

        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO worker_profiles (user_id,skills,work_province,preferred_work_mode,available_from,profile_visibility) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE skills=VALUES(skills),work_province=VALUES(work_province),preferred_work_mode=VALUES(preferred_work_mode),available_from=VALUES(available_from),profile_visibility=VALUES(profile_visibility)')
            ->execute([$workerId, $skillsInput, FLEXJOB_PROVINCE, $workMode, $availableFrom ?: null, $visibility]);
        matching_sync_worker_skills($pdo, $workerId, $skillsInput);
        matching_sync_worker_preferences($pdo, $workerId, $preferences);
        matching_sync_worker_work_interests($pdo, $workerId, $selectedWorkInterestIds);
        $pdo->commit();

        flash('success', 'บันทึกแบบสำรวจแล้ว งานแนะนำได้รับการปรับให้ตรงกับคุณมากขึ้น');
        redirect('worker/index.php');
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $exception->getMessage();
    }
}

$profileStatement = $pdo->prepare('SELECT skills,preferred_work_mode,available_from,profile_visibility FROM worker_profiles WHERE user_id=?');
$profileStatement->execute([$workerId]);
$profile = $profileStatement->fetch() ?: [];
$skillStatement = $pdo->prepare("SELECT GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR ', ') FROM worker_skills ws JOIN skills s ON s.skill_id=ws.skill_id WHERE ws.worker_user_id=?");
$skillStatement->execute([$workerId]);
$profile['skills'] = $skillStatement->fetchColumn() ?: ($profile['skills'] ?? '');
$preferenceStatement = $pdo->prepare('SELECT jc.category_slug FROM worker_job_preferences wjp JOIN job_categories jc ON jc.job_category_id=wjp.job_category_id WHERE wjp.worker_user_id=?');
$preferenceStatement->execute([$workerId]);
$selectedPreferences = $preferenceStatement->fetchAll(PDO::FETCH_COLUMN);
$workInterestStatement = $pdo->prepare('SELECT work_interest_id FROM worker_work_interests WHERE worker_user_id=? ORDER BY work_interest_id');
$workInterestStatement->execute([$workerId]);
$selectedWorkInterestIds = array_map('intval', $workInterestStatement->fetchAll(PDO::FETCH_COLUMN));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) {
    $profile['skills'] = trim((string) ($_POST['skills'] ?? ''));
    $profile['preferred_work_mode'] = (string) ($_POST['preferred_work_mode'] ?? 'any');
    $profile['available_from'] = trim((string) ($_POST['available_from'] ?? ''));
    $profile['profile_visibility'] = (string) ($_POST['profile_visibility'] ?? 'application_only');
    $selectedPreferences = array_map('strval', (array) ($_POST['job_preferences'] ?? []));
    $selectedWorkInterestIds = array_map('intval', (array) ($_POST['work_interests'] ?? []));
}

$pageTitle = 'แบบสำรวจ Matching | FLEXJOB';
$pageStyles = ['matching', 'matching-survey'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container py-5 survey-page">
    <div class="row justify-content-center"><div class="col-lg-9 col-xl-8">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
            <div><p class="eyebrow mb-2">JOB MATCHING SURVEY</p><h1 class="h2 mb-2">บอกเราว่างานแบบไหนเหมาะกับคุณ</h1><p class="text-secondary mb-0">ใช้เวลาประมาณ 1–2 นาที คำตอบแก้ไขภายหลังได้ และใช้แนะนำงานในจังหวัด<?= e(FLEXJOB_PROVINCE) ?>เท่านั้น</p></div>
            <a class="btn btn-link text-decoration-none px-0 flex-shrink-0" href="<?= BASE_URL ?>/worker/index.php">ไว้ทำภายหลัง</a>
        </div>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif ?>
        <div class="survey-progress mb-3" aria-live="polite"><div class="d-flex justify-content-between small mb-2"><b id="surveyStepLabel">ขั้นตอน 1 จาก 5</b><span id="surveyPercent">20%</span></div><div class="progress"><div class="progress-bar bg-success" id="surveyProgressBar" style="width:20%"></div></div></div>
        <form method="post" class="card border-0 shadow-sm" id="matchingSurvey" novalidate>
            <?= csrf_field() ?>
            <div class="card-body p-4 p-md-5">
                <section class="survey-step" data-survey-step="0">
                    <p class="eyebrow">STEP 1</p><h2 class="h4">คุณสนใจทำงานด้านไหน?</h2><p class="text-secondary">เลือกได้ 1–5 หมวด นี่คือสิ่งที่คุณอยากทำ ไม่จำเป็นต้องเป็นทักษะที่เชี่ยวชาญแล้ว</p>
                    <div class="survey-options mt-4"><?php foreach ($workInterests as $interest): ?><label class="survey-option"><input class="form-check-input" type="checkbox" name="work_interests[]" value="<?= $interest['work_interest_id'] ?>" <?= in_array((int) $interest['work_interest_id'], $selectedWorkInterestIds, true) ? 'checked' : '' ?>><span><b><?= e($interest['interest_name']) ?></b><small><?= e($interestDescriptions[$interest['interest_slug']] ?? '') ?></small></span></label><?php endforeach ?></div>
                    <div class="invalid-feedback d-block" id="workInterestError" hidden>กรุณาเลือก 1–5 หมวด</div>
                </section>
                <section class="survey-step" data-survey-step="1">
                    <p class="eyebrow">STEP 2</p><h2 class="h4">คุณสนใจรูปแบบการจ้างประเภทใด?</h2><p class="text-secondary">เลือกได้มากกว่า 1 ประเภท เช่น สนใจทั้งพาร์ทไทม์และฟรีแลนซ์</p>
                    <div class="survey-options mt-4"><?php foreach (['part_time' => ['พาร์ทไทม์', 'งานเป็นกะหรือเป็นช่วงเวลา'], 'event' => ['งานอีเวนต์', 'งานรายวันและกิจกรรมในพื้นที่'], 'freelance' => ['ฟรีแลนซ์', 'งานเป็นชิ้นหรือเป็นโปรเจกต์']] as $value => [$label, $description]): ?><label class="survey-option"><input class="form-check-input" type="checkbox" name="job_preferences[]" value="<?= $value ?>" <?= in_array($value, $selectedPreferences, true) ? 'checked' : '' ?>><span><b><?= $label ?></b><small><?= $description ?></small></span></label><?php endforeach ?></div>
                    <div class="invalid-feedback d-block" id="preferenceError" hidden>กรุณาเลือกอย่างน้อย 1 ประเภท</div>
                </section>
                <section class="survey-step" data-survey-step="2">
                    <p class="eyebrow">STEP 3</p><h2 class="h4">คุณมีทักษะหรือความสามารถอะไรบ้าง?</h2><p class="text-secondary">ระบุเฉพาะสิ่งที่ทำได้จริง เช่น การขาย, Excel, Canva หรือถ่ายภาพ</p>
                    <label class="form-label mt-3" for="surveySkills">ทักษะของคุณ</label><textarea class="form-control" id="surveySkills" name="skills" rows="4" placeholder="เช่น การสื่อสาร, Excel, Canva" required><?= e($profile['skills'] ?? '') ?></textarea><div class="form-text">คั่นแต่ละทักษะด้วยเครื่องหมายจุลภาค ระบบรับสูงสุด 30 ทักษะ</div>
                </section>
                <section class="survey-step" data-survey-step="3">
                    <p class="eyebrow">STEP 4</p><h2 class="h4">คุณต้องการทำงานรูปแบบใด?</h2><p class="text-secondary">ทุกงานอยู่ในขอบเขตจังหวัด<?= e(FLEXJOB_PROVINCE) ?> ส่วน “ออนไลน์” หมายถึงทำงานจากที่ใดก็ได้โดยไม่ต้องเข้าสถานที่ทำงาน</p>
                    <div class="survey-options mt-4"><?php foreach (['any' => ['ได้ทุกรูปแบบ', 'พร้อมพิจารณาทั้งงานที่สถานที่ ออนไลน์ และ Hybrid'], 'onsite' => ['ทำงานที่สถานที่', 'เดินทางไปยังสถานที่ทำงานในบุรีรัมย์'], 'remote' => ['ทำงานออนไลน์', 'ทำงานผ่านอินเทอร์เน็ต ไม่ต้องเข้าสถานที่'], 'hybrid' => ['Hybrid', 'สลับระหว่างออนไลน์และเข้าสถานที่']] as $value => [$label, $description]): ?><label class="survey-option"><input class="form-check-input" type="radio" name="preferred_work_mode" value="<?= $value ?>" <?= ($profile['preferred_work_mode'] ?? 'any') === $value ? 'checked' : '' ?> required><span><b><?= $label ?></b><small><?= $description ?></small></span></label><?php endforeach ?></div>
                </section>
                <section class="survey-step" data-survey-step="4">
                    <p class="eyebrow">STEP 5</p><h2 class="h4">คุณพร้อมเริ่มงานเมื่อไร?</h2><p class="text-secondary">ข้อมูลนี้แสดงให้ผู้ว่าจ้างประกอบการพิจารณา แต่ยังไม่ใช้คำนวณคะแนน Matching</p>
                    <label class="form-label mt-3" for="availableFrom">พร้อมเริ่มงานตั้งแต่ <span class="text-secondary">(ไม่บังคับ)</span></label><input class="form-control" id="availableFrom" type="date" name="available_from" value="<?= e($profile['available_from'] ?? '') ?>">
                    <fieldset class="mt-4"><legend class="h6">การแสดงโปรไฟล์ต่อนายจ้าง</legend><div class="border rounded p-3 mt-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="profile_visibility" value="searchable" id="profileVisibility" <?= ($profile['profile_visibility'] ?? 'application_only') === 'searchable' ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="profileVisibility">ให้นายจ้างใน FLEXJOB ค้นพบโปรไฟล์ของฉัน</label></div><p class="small text-secondary mt-2 mb-0">เมื่อเปิดใช้งาน เฉพาะนายจ้างที่ผ่านการยืนยันและมีประกาศงานที่เปิดรับสมัครเท่านั้นที่ค้นหาคุณและส่งคำเชิญให้สมัครได้ ระบบจะไม่แสดงอีเมล เบอร์โทร Resume หรือ Portfolio ในหน้าค้นหา</p></div><p class="small text-secondary mt-2 mb-0">หากไม่เปิด คุณยังค้นหาและสมัครงานเองได้ตามปกติ และนายจ้างจะเห็นข้อมูลติดต่อเมื่อคุณสมัครงานของเขาแล้ว</p></fieldset>
                    <div class="alert alert-light border small mt-4 mb-0">พื้นที่ถูกกำหนดเป็นจังหวัด<?= e(FLEXJOB_PROVINCE) ?>อัตโนมัติ ระบบไม่ใช้จังหวัดเพิ่มคะแนน Matching</div>
                </section>
                <div class="survey-actions d-flex flex-column-reverse flex-sm-row justify-content-between gap-2 mt-5"><button class="btn btn-outline-secondary" id="surveyPrevious" type="button">ย้อนกลับ</button><button class="btn btn-success px-4" id="surveyNext" type="button">ถัดไป</button><button class="btn btn-success px-4" id="surveySubmit" type="submit">บันทึกและดูงานแนะนำ</button></div>
            </div>
        </form>
    </div></div>
</main>
<script>
(() => {
    const form = document.querySelector('#matchingSurvey');
    const steps = [...form.querySelectorAll('[data-survey-step]')];
    const previous = document.querySelector('#surveyPrevious');
    const next = document.querySelector('#surveyNext');
    const submit = document.querySelector('#surveySubmit');
    const label = document.querySelector('#surveyStepLabel');
    const percent = document.querySelector('#surveyPercent');
    const bar = document.querySelector('#surveyProgressBar');
    const workInterestError = document.querySelector('#workInterestError');
    const preferenceError = document.querySelector('#preferenceError');
    let current = 0;

    function showStep(index) {
        current = Math.max(0, Math.min(steps.length - 1, index));
        steps.forEach((step, position) => step.hidden = position !== current);
        const progress = Math.round((current + 1) * 100 / steps.length);
        label.textContent = `ขั้นตอน ${current + 1} จาก ${steps.length}`;
        percent.textContent = `${progress}%`;
        bar.style.width = `${progress}%`;
        previous.hidden = current === 0;
        next.hidden = current === steps.length - 1;
        submit.hidden = current !== steps.length - 1;
        steps[current].querySelector('input, textarea')?.focus({preventScroll: true});
        window.scrollTo({top: form.offsetTop - 110, behavior: 'smooth'});
    }
    function currentStepIsValid() {
        if (current === 0) {
            const count = form.querySelectorAll('input[name="work_interests[]"]:checked').length;
            const valid = count >= 1 && count <= 5;
            workInterestError.hidden = valid;
            return valid;
        }
        if (current === 1) {
            const selected = form.querySelectorAll('input[name="job_preferences[]"]:checked').length > 0;
            preferenceError.hidden = selected;
            return selected;
        }
        const fields = [...steps[current].querySelectorAll('input, textarea, select')];
        const invalid = fields.find(field => !field.checkValidity());
        if (invalid) invalid.reportValidity();
        return !invalid;
    }
    next.addEventListener('click', () => { if (currentStepIsValid()) showStep(current + 1); });
    previous.addEventListener('click', () => showStep(current - 1));
    form.querySelectorAll('input[name="work_interests[]"]').forEach(input => input.addEventListener('change', event => {
        const selected = form.querySelectorAll('input[name="work_interests[]"]:checked');
        if (selected.length > 5) {
            event.currentTarget.checked = false;
            workInterestError.textContent = 'เลือกได้สูงสุด 5 หมวด';
            workInterestError.hidden = false;
        } else {
            workInterestError.textContent = 'กรุณาเลือก 1–5 หมวด';
            workInterestError.hidden = true;
        }
    }));
    form.addEventListener('submit', event => { if (!currentStepIsValid()) event.preventDefault(); });
    showStep(0);
})();
</script>
<?php require APP_ROOT . '/partials/footer.php'; ?>
