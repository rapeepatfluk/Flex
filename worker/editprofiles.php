<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$pdo = db();
$workerId = user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $profileImage = upload_file('profile_image', ['jpg', 'jpeg', 'png', 'webp'], 'profile-images');
        $resumeFile = upload_file('resume_file', ['pdf', 'doc', 'docx'], 'resumes');
        $portfolioFile = upload_file('portfolio_file', ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'zip'], 'portfolios');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $workMode = $_POST['preferred_work_mode'] ?? 'any';
        // A missing checkbox is the private option. Unknown values also fail closed.
        $visibility = ($_POST['profile_visibility'] ?? '') === 'searchable' ? 'searchable' : 'application_only';

        if ($firstName === '' || $lastName === '') {
            throw new RuntimeException('กรุณากรอกชื่อและนามสกุล');
        }
        if (!in_array($workMode, ['any', 'onsite', 'remote', 'hybrid'], true)) throw new RuntimeException('รูปแบบงานไม่ถูกต้อง');

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET first_name=?, last_name=?, phone=? WHERE user_id=?')
            ->execute([$firstName, $lastName, trim($_POST['phone'] ?? ''), $workerId]);

        matching_sync_worker_skill_selection($pdo, $workerId, (array) ($_POST['skill_ids'] ?? []), trim((string) ($_POST['custom_skills'] ?? '')));
        $pdo->prepare('INSERT INTO worker_profiles (user_id, professional_headline, biography, profile_image_path, resume_file_path, portfolio_file_path, portfolio_url, profile_visibility, work_province, preferred_work_mode, available_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE professional_headline=VALUES(professional_headline), biography=VALUES(biography), profile_image_path=COALESCE(VALUES(profile_image_path), profile_image_path), resume_file_path=COALESCE(VALUES(resume_file_path), resume_file_path), portfolio_file_path=COALESCE(VALUES(portfolio_file_path), portfolio_file_path), portfolio_url=VALUES(portfolio_url), profile_visibility=VALUES(profile_visibility), work_province=VALUES(work_province), preferred_work_mode=VALUES(preferred_work_mode), available_from=VALUES(available_from)')
            ->execute([$workerId, trim($_POST['headline'] ?? ''), trim($_POST['introduce'] ?? ''), $profileImage, $resumeFile, $portfolioFile, trim($_POST['portfolio_url'] ?? ''), $visibility, FLEXJOB_PROVINCE, $workMode, ($_POST['available_from'] ?? '') ?: null]);
        matching_sync_worker_preferences($pdo, $workerId, (array) ($_POST['job_preferences'] ?? []));
        matching_sync_worker_work_interests($pdo, $workerId, (array) ($_POST['work_interests'] ?? []));

        $pdo->commit();
        $_SESSION['user']['name'] = $firstName . ' ' . $lastName;
        flash('success', 'บันทึกข้อมูลโปรไฟล์แล้ว');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('worker/editprofiles.php');
}

$profileStmt = $pdo->prepare('SELECT u.first_name, u.last_name, u.email, u.phone, wp.professional_headline, wp.biography, wp.profile_image_path, wp.resume_file_path, wp.portfolio_file_path, wp.portfolio_url, wp.profile_visibility, wp.work_province, wp.preferred_work_mode, wp.available_from FROM users u LEFT JOIN worker_profiles wp ON wp.user_id=u.user_id WHERE u.user_id=?');
$profileStmt->execute([$workerId]);
$profile = $profileStmt->fetch() ?: [];
$selectedSkillStmt = $pdo->prepare('SELECT skill_id FROM worker_skills WHERE worker_user_id=? ORDER BY skill_id');
$selectedSkillStmt->execute([$workerId]);
$selectedSkillIds = array_map('intval', $selectedSkillStmt->fetchAll(PDO::FETCH_COLUMN));
$skillCategories = matching_skill_catalog($pdo, $selectedSkillIds);
require_once APP_ROOT . '/partials/skill-selector.php';
$preferenceStmt = $pdo->prepare('SELECT jc.category_slug FROM worker_job_preferences wjp JOIN job_categories jc ON jc.job_category_id=wjp.job_category_id WHERE wjp.worker_user_id=?');
$preferenceStmt->execute([$workerId]);
$selectedPreferences = $preferenceStmt->fetchAll(PDO::FETCH_COLUMN);
$workInterests = matching_work_interests($pdo);
$workInterestStmt = $pdo->prepare('SELECT work_interest_id FROM worker_work_interests WHERE worker_user_id=? ORDER BY work_interest_id');
$workInterestStmt->execute([$workerId]);
$selectedWorkInterestIds = array_map('intval', $workInterestStmt->fetchAll(PDO::FETCH_COLUMN));
$pageTitle = 'แก้ไขโปรไฟล์ | FLEXJOB';
$pageStyles = ['worker-profile-edit', 'skill-selector'];
require APP_ROOT . '/partials/header.php';
?>

<main class="profile-edit-page"><div class="container py-4 py-lg-5">
    <header class="profile-edit-header card border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row align-items-md-center gap-3">
            <div class="profile-header-avatar" aria-hidden="true">
                <?php if (!empty($profile['profile_image_path'])): ?><img src="<?= BASE_URL . '/' . e($profile['profile_image_path']) ?>" alt=""><?php else: ?><?= e(mb_substr(($profile['first_name'] ?? 'F'), 0, 1)) ?><?php endif ?>
            </div>
            <div class="flex-grow-1"><p class="eyebrow mb-2">WORKER PROFILE</p><h1 class="h2 mb-1">แก้ไขโปรไฟล์</h1><p class="mb-0">ข้อมูลนี้ใช้แนะนำงาน และจะแสดงแก่ผู้ว่าจ้างตามสิทธิ์ที่คุณเลือก</p></div>
            <span class="profile-header-note">บันทึกได้ทุกเมื่อ</span>
        </div>
    </header>

    <section class="profile-edit-guide row g-2 g-md-3 mb-4" aria-label="แนวทางการกรอกโปรไฟล์">
        <div class="col-md-4"><div class="profile-guide-item h-100"><span>01</span><div><b>บอกตัวตน</b><small>ใส่ข้อมูลและคำแนะนำตัวให้ชัดเจน</small></div></div></div>
        <div class="col-md-4"><div class="profile-guide-item h-100"><span>02</span><div><b>เลือกงานและทักษะ</b><small>ช่วยให้ระบบแนะนำงานได้ตรงขึ้น</small></div></div></div>
        <div class="col-md-4"><div class="profile-guide-item h-100"><span>03</span><div><b>เพิ่ม Resume และผลงาน</b><small>ให้ผู้ว่าจ้างตัดสินใจได้ง่ายขึ้น</small></div></div></div>
    </section>

    <form method="post" enctype="multipart/form-data" class="card shadow-sm border-0 profile-edit-form">
        <?= csrf_field() ?>
        <div class="card-body p-4 p-md-5">
            <h2 class="profile-section-title h5 mb-3">ข้อมูลส่วนตัว</h2>
            <div class="profile-section row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" for="profile_image">รูปโปรไฟล์</label>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                        <?php if (!empty($profile['profile_image_path'])): ?><img class="profile-photo-preview" src="<?= BASE_URL . '/' . e($profile['profile_image_path']) ?>" alt="รูปโปรไฟล์ปัจจุบัน"><?php else: ?><div class="profile-photo-placeholder" aria-hidden="true"><?= e(mb_substr(($profile['first_name'] ?? 'F'), 0, 1)) ?></div><?php endif ?>
                        <div class="flex-grow-1"><input id="profile_image" class="form-control" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text">ใช้รูปหน้าตรงที่เห็นใบหน้าชัดเจน รองรับ JPG, PNG หรือ WebP ขนาดไม่เกิน 8 MB</div></div>
                    </div>
                </div>
                <div class="col-md-6"><label class="form-label" for="first_name">ชื่อ</label><input id="first_name" class="form-control" name="first_name" value="<?= e($profile['first_name'] ?? '') ?>" autocomplete="given-name" required></div>
                <div class="col-md-6"><label class="form-label" for="last_name">นามสกุล</label><input id="last_name" class="form-control" name="last_name" value="<?= e($profile['last_name'] ?? '') ?>" autocomplete="family-name" required></div>
                <div class="col-md-6"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" type="tel" name="phone" value="<?= e($profile['phone'] ?? '') ?>" autocomplete="tel"></div>
                <div class="col-md-6"><label class="form-label" for="email">อีเมล</label><input id="email" class="form-control" type="email" value="<?= e($profile['email'] ?? '') ?>" autocomplete="email" disabled></div>
            </div>
            <h2 class="profile-section-title h5 mb-3">แนะนำตัวและผลงาน</h2>
            <div class="profile-section row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" for="headline">คำโปรยแนะนำตัวสั้น ๆ</label>
                    <input id="headline" class="form-control" name="headline" maxlength="150" value="<?= e($profile['professional_headline'] ?? '') ?>" placeholder="เช่น นักศึกษาการตลาด ใช้ Canva ได้ พร้อมทำงานเสาร์–อาทิตย์">
                    <div class="form-text">เขียนสั้น ๆ 1 ประโยค บอกว่าคุณถนัดอะไรและกำลังมองหางานแบบไหน</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="introduce">แนะนำตัว</label>
                    <textarea id="introduce" class="form-control" name="introduce" rows="4" placeholder="เล่าประสบการณ์ จุดเด่น หรือประเภทงานที่สนใจ"><?= e($profile['biography'] ?? '') ?></textarea>
                </div>
            </div>

            <h2 class="profile-section-title h5 mb-3">ความสามารถและงานที่สนใจ</h2>
            <div class="profile-section row g-3 mb-4">
                <div class="col-12 profile-choice-row">
                    <label class="form-label">รูปแบบการจ้างที่สนใจ</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach (['part_time' => 'พาร์ทไทม์', 'event' => 'งานอีเวนต์', 'freelance' => 'ฟรีแลนซ์'] as $value => $label): ?>
                            <div class="form-check profile-choice-pill">
                                <input class="form-check-input" type="checkbox" name="job_preferences[]" value="<?= $value ?>" id="pref_<?= $value ?>" <?= in_array($value, $selectedPreferences, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pref_<?= $value ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">งานที่สนใจ <span class="text-secondary">(เลือกได้ไม่เกิน 5 หมวด)</span></label>
                    <div class="row g-2">
                        <?php foreach ($workInterests as $interest): ?>
                            <div class="col-md-6"><div class="form-check border rounded p-3 h-100 profile-interest-option">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="work_interests[]" value="<?= $interest['work_interest_id'] ?>" id="work_interest_<?= $interest['work_interest_id'] ?>" <?= in_array((int) $interest['work_interest_id'], $selectedWorkInterestIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="work_interest_<?= $interest['work_interest_id'] ?>"><?= e($interest['interest_name']) ?></label>
                            </div></div>
                        <?php endforeach ?>
                    </div>
                    <div class="form-text">งานที่สนใจใช้บอกสิ่งที่อยากทำ ส่วนทักษะใช้บอกสิ่งที่คุณทำได้จริง</div>
                    <div class="text-danger small mt-1" id="editInterestError" hidden>เลือกได้สูงสุด 5 หมวด</div>
                </div>
                <div class="col-12">
                    <?php render_skill_selector('workerSkills', 'ทักษะที่ทำได้จริง', 'เลือกจากรายการเพื่อใช้แนะนำงานที่ตรงกับความสามารถของคุณ', $skillCategories, $selectedSkillIds, 'skill_ids[]', 'custom_skills'); ?>
                </div>
            </div>

            <h2 class="profile-section-title h5 mb-3">ความต้องการทำงานและการค้นพบโปรไฟล์</h2>
            <div class="profile-section row g-3 mb-4">
                <div class="col-md-6"><label class="form-label">พื้นที่ให้บริการ</label><input class="form-control" value="จังหวัด<?= e(FLEXJOB_PROVINCE) ?>" disabled><div class="form-text">FLEXJOB เป็นเว็บไซต์หางานในจังหวัดบุรีรัมย์ จึงไม่ต้องเลือกจังหวัด</div></div>
                <div class="col-md-6"><label class="form-label" for="preferred_work_mode">รูปแบบงานที่ต้องการ</label><select id="preferred_work_mode" class="form-select" name="preferred_work_mode"><?php foreach (['any' => 'ได้ทุกรูปแบบ', 'onsite' => 'ทำงานที่สถานที่', 'remote' => 'ทำงานออนไลน์', 'hybrid' => 'Hybrid'] as $value => $label): ?><option value="<?= $value ?>" <?= ($profile['preferred_work_mode'] ?? 'any') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
                <div class="col-md-6"><label class="form-label" for="available_from">พร้อมเริ่มงานตั้งแต่</label><input id="available_from" class="form-control" type="date" name="available_from" value="<?= e($profile['available_from'] ?? '') ?>"></div>
                <div class="col-md-6"><fieldset class="border rounded p-3 h-100"><legend class="h6 mb-2">การแสดงโปรไฟล์ต่อนายจ้าง</legend><div class="form-check"><input id="profile_visibility" class="form-check-input" type="checkbox" name="profile_visibility" value="searchable" <?= ($profile['profile_visibility'] ?? 'application_only') === 'searchable' ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="profile_visibility">ให้นายจ้างใน FLEXJOB ค้นพบโปรไฟล์ของฉัน</label></div><div class="form-text mt-2">เฉพาะนายจ้างที่ผ่านการยืนยันและมีประกาศที่เปิดรับเท่านั้น ระบบไม่แสดงอีเมล เบอร์โทร Resume หรือ Portfolio ในหน้าค้นหา</div><div class="form-text">หากไม่เปิด คุณยังสมัครงานเองได้ตามปกติ</div></fieldset></div>
            </div>
            <h2 class="profile-section-title h5 mb-3">ผลงานและเอกสารประกอบ</h2>
            <div class="profile-section profile-portfolio-section row g-3 mb-4">
                <div class="col-12"><p class="profile-section-helper mb-0">เพิ่ม Portfolio หรือ Certificate เพื่อให้ผู้ว่าจ้างรู้จักคุณมากขึ้น</p></div>
                <div class="col-md-6">
                    <label class="form-label" for="portfolio_file">Portfolio file</label>
                    <input id="portfolio_file" class="form-control" type="file" name="portfolio_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.zip">
                    <?php if (!empty($profile['portfolio_file_path'])): ?><div class="small text-success mt-2">✓ <a class="link-success" href="<?= BASE_URL ?>/download.php?type=profile_portfolio&id=<?= $workerId ?>" target="_blank" rel="noopener">เปิดไฟล์ Portfolio ที่อัปโหลดแล้ว</a></div><?php endif ?>
                    <div class="form-text">PDF หรือรูปภาพ — รวม Certificate ไว้ใน Portfolio ได้</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="portfolio_url">Portfolio URL</label>
                    <input id="portfolio_url" class="form-control" type="url" name="portfolio_url" value="<?= e($profile['portfolio_url'] ?? '') ?>" placeholder="https://...">
                    <div class="form-text">วางลิงก์ Google Drive, Behance, เว็บไซต์ หรือผลงานออนไลน์</div>
                </div>
            </div>
<h2 class="profile-section-title h5 mb-3">Resume</h2>
            <div class="profile-section profile-resume-section row g-3 mb-0">
                <div class="col-md-6"><label class="form-label" for="resume_file">Resume</label><?php if (!empty($profile['resume_file_path'])): ?><div class="small text-success mb-2">✓ <a class="link-success" href="<?= BASE_URL ?>/download.php?type=profile_resume&id=<?= $workerId ?>" target="_blank" rel="noopener">เปิด Resume ที่อัปโหลดแล้ว</a></div><?php endif ?><input id="resume_file" class="form-control" type="file" name="resume_file" accept=".pdf,.doc,.docx">
                    <div class="form-text">PDF, DOC หรือ DOCX</div>
                </div>
            </div>

            <div class="profile-edit-actions d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mt-4"><p class="mb-0">ข้อมูลที่กรอกจะช่วยให้ผู้ว่าจ้างรู้จักคุณได้มากขึ้น</p><button class="btn btn-primary px-4 py-2" type="submit">บันทึกโปรไฟล์</button></div>
        </div>
    </form>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/worker-profile-edit.js" defer></script>
<?php require APP_ROOT . '/partials/footer.php'; ?>
