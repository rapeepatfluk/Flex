<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$pdo = db();
$workerId = user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $resumeFile = upload_file('resume_file', ['pdf', 'doc', 'docx'], 'resumes');
        $portfolioFile = upload_file('portfolio_file', ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'zip'], 'portfolios');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $workMode = $_POST['preferred_work_mode'] ?? 'any';
        $visibility = $_POST['profile_visibility'] ?? 'application_only';

        if ($firstName === '' || $lastName === '') {
            throw new RuntimeException('กรุณากรอกชื่อและนามสกุล');
        }
        if (!in_array($workMode, ['any', 'onsite', 'remote', 'hybrid'], true)) throw new RuntimeException('รูปแบบงานไม่ถูกต้อง');
        if (!in_array($visibility, ['application_only', 'searchable'], true)) throw new RuntimeException('การเปิดเผยโปรไฟล์ไม่ถูกต้อง');

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET first_name=?, last_name=?, phone=? WHERE user_id=?')
            ->execute([$firstName, $lastName, trim($_POST['phone'] ?? ''), $workerId]);

        $skillsInput = trim($_POST['skills'] ?? '');
        $pdo->prepare('INSERT INTO worker_profiles (user_id, professional_headline, biography, skills, resume_file_path, portfolio_file_path, portfolio_url, profile_visibility, work_province, preferred_work_mode, available_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE professional_headline=VALUES(professional_headline), biography=VALUES(biography), skills=VALUES(skills), resume_file_path=COALESCE(VALUES(resume_file_path), resume_file_path), portfolio_file_path=COALESCE(VALUES(portfolio_file_path), portfolio_file_path), portfolio_url=VALUES(portfolio_url), profile_visibility=VALUES(profile_visibility), work_province=VALUES(work_province), preferred_work_mode=VALUES(preferred_work_mode), available_from=VALUES(available_from)')
            ->execute([$workerId, trim($_POST['headline'] ?? ''), trim($_POST['introduce'] ?? ''), $skillsInput, $resumeFile, $portfolioFile, trim($_POST['portfolio_url'] ?? ''), $visibility, trim($_POST['work_province'] ?? ''), $workMode, ($_POST['available_from'] ?? '') ?: null]);
        matching_sync_worker_skills($pdo, $workerId, $skillsInput);
        matching_sync_worker_preferences($pdo, $workerId, (array) ($_POST['job_preferences'] ?? []));

        $pdo->commit();
        $_SESSION['user']['name'] = $firstName . ' ' . $lastName;
        flash('success', 'บันทึกข้อมูลโปรไฟล์แล้ว');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('worker/editprofiles.php');
}

$profileStmt = $pdo->prepare('SELECT u.first_name, u.last_name, u.email, u.phone, wp.professional_headline, wp.biography, wp.skills, wp.resume_file_path, wp.portfolio_file_path, wp.portfolio_url, wp.profile_visibility, wp.work_province, wp.preferred_work_mode, wp.available_from FROM users u LEFT JOIN worker_profiles wp ON wp.user_id=u.user_id WHERE u.user_id=?');
$profileStmt->execute([$workerId]);
$profile = $profileStmt->fetch() ?: [];
$skillStmt = $pdo->prepare("SELECT GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR ', ') FROM worker_skills ws JOIN skills s ON s.skill_id=ws.skill_id WHERE ws.worker_user_id=?");
$skillStmt->execute([$workerId]);
$structuredSkills = $skillStmt->fetchColumn();
if ($structuredSkills) $profile['skills'] = $structuredSkills;
$preferenceStmt = $pdo->prepare('SELECT jc.category_slug FROM worker_job_preferences wjp JOIN job_categories jc ON jc.job_category_id=wjp.job_category_id WHERE wjp.worker_user_id=?');
$preferenceStmt->execute([$workerId]);
$selectedPreferences = $preferenceStmt->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'แก้ไขโปรไฟล์ | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">WORKER PROFILE</p>
            <h1 class="h2 mb-1">แก้ไขโปรไฟล์</h1>
            <p class="text-secondary mb-0">ข้อมูลนี้ใช้แนะนำงาน และจะแสดงแก่ผู้ว่าจ้างตามสิทธิ์ที่คุณเลือก</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="card shadow-sm border-0">
        <?= csrf_field() ?>
        <div class="card-body p-4 p-md-5">
            <h2 class="h5 mb-3">ข้อมูลส่วนตัว</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label" for="first_name">ชื่อ</label><input id="first_name" class="form-control" name="first_name" value="<?= e($profile['first_name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label" for="last_name">นามสกุล</label><input id="last_name" class="form-control" name="last_name" value="<?= e($profile['last_name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" name="phone" value="<?= e($profile['phone'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label" for="email">อีเมล</label><input id="email" class="form-control" value="<?= e($profile['email'] ?? '') ?>" disabled></div>
            </div>

            <h2 class="h5 mb-3">แนะนำตัวและผลงาน</h2>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" for="headline">คำโปรยแนะนำตัวสั้น ๆ</label>
                    <input id="headline" class="form-control" name="headline" maxlength="150" value="<?= e($profile['professional_headline'] ?? '') ?>" placeholder="เช่น นักศึกษาการตลาด ใช้ Canva ได้ พร้อมทำงานเสาร์–อาทิตย์">
                    <div class="form-text">เขียนสั้น ๆ 1 ประโยค บอกว่าคุณถนัดอะไรและกำลังมองหางานแบบไหน ข้อความนี้เป็นสิ่งแรกที่ช่วยให้ผู้ว่าจ้างรู้จักคุณ</div>
                </div>
                <div class="col-12"><label class="form-label" for="introduce">แนะนำตัว</label><textarea id="introduce" class="form-control" name="introduce" rows="4" placeholder="เล่าประสบการณ์ จุดเด่น หรือประเภทงานที่สนใจ"><?= e($profile['biography'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label" for="skills">ทักษะ</label><input id="skills" class="form-control" name="skills" value="<?= e($profile['skills'] ?? '') ?>" placeholder="เช่น Canva, Excel, การสื่อสาร"><div class="form-text">คั่นแต่ละทักษะด้วยเครื่องหมายจุลภาค ระบบจะใช้ข้อมูลนี้ในการจับคู่งาน</div></div>
                <div class="col-md-6"><label class="form-label" for="portfolio_file">Portfolio file</label><?php if (!empty($profile['portfolio_file_path'])): ?><div class="small text-success mb-2">✓ <a class="link-success" href="<?= BASE_URL ?>/download.php?type=profile_portfolio&id=<?= $workerId ?>" target="_blank" rel="noopener">เปิดไฟล์ Portfolio ที่อัปโหลดแล้ว</a></div><?php endif ?><input id="portfolio_file" class="form-control" type="file" name="portfolio_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.zip">
                    <div class="form-text">PDF หรือ รูปภาพ — รวม Certificate ไว้ใน Portfolio ได้</div>
                </div>
                <div class="col-md-6"><label class="form-label" for="portfolio_url">Portfolio URL</label><input id="portfolio_url" class="form-control" type="url" name="portfolio_url" value="<?= e($profile['portfolio_url'] ?? '') ?>" placeholder="https://...">
                    <div class="form-text">วางลิงก์ Google Drive, Behance, เว็บไซต์ หรือผลงานออนไลน์ และแนบ Certificate ไว้ในลิงก์เดียวกันได้</div>
                </div>
            </div>

            <h2 class="h5 mb-3">ความต้องการทำงานและการค้นพบโปรไฟล์</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label" for="work_province">จังหวัดที่สะดวกทำงาน</label><input id="work_province" class="form-control" name="work_province" value="<?= e($profile['work_province'] ?? '') ?>" placeholder="เช่น กรุงเทพมหานคร"></div>
                <div class="col-md-6"><label class="form-label" for="preferred_work_mode">รูปแบบงานที่ต้องการ</label><select id="preferred_work_mode" class="form-select" name="preferred_work_mode"><?php foreach (['any' => 'ได้ทุกรูปแบบ', 'onsite' => 'ทำงานที่สถานที่', 'remote' => 'ทำงานออนไลน์', 'hybrid' => 'Hybrid'] as $value => $label): ?><option value="<?= $value ?>" <?= ($profile['preferred_work_mode'] ?? 'any') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
                <div class="col-md-6"><label class="form-label" for="available_from">พร้อมเริ่มงานตั้งแต่</label><input id="available_from" class="form-control" type="date" name="available_from" value="<?= e($profile['available_from'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label" for="profile_visibility">ใครค้นหาโปรไฟล์นี้ได้</label><select id="profile_visibility" class="form-select" name="profile_visibility"><option value="application_only" <?= ($profile['profile_visibility'] ?? 'application_only') === 'application_only' ? 'selected' : '' ?>>เฉพาะผู้ว่าจ้างที่ฉันสมัครงาน</option><option value="searchable" <?= ($profile['profile_visibility'] ?? '') === 'searchable' ? 'selected' : '' ?>>ผู้ว่าจ้างที่ยืนยันแล้วค้นหาได้</option></select><div class="form-text">หน้าค้นหาจะไม่แสดงอีเมล เบอร์โทร Resume หรือ Portfolio</div></div>
                <div class="col-12"><label class="form-label">ประเภทงานที่สนใจ</label><div class="d-flex flex-wrap gap-3"><?php foreach (['part_time' => 'พาร์ทไทม์', 'event' => 'งานอีเวนต์', 'freelance' => 'ฟรีแลนซ์'] as $value => $label): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="job_preferences[]" value="<?= $value ?>" id="pref_<?= $value ?>" <?= in_array($value, $selectedPreferences, true) ? 'checked' : '' ?>><label class="form-check-label" for="pref_<?= $value ?>"><?= $label ?></label></div><?php endforeach ?></div></div>
            </div>

            <h2 class="h5 mb-3">Resume</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="resume_file">Resume</label><?php if (!empty($profile['resume_file_path'])): ?><div class="small text-success mb-2">✓ <a class="link-success" href="<?= BASE_URL ?>/download.php?type=profile_resume&id=<?= $workerId ?>" target="_blank" rel="noopener">เปิด Resume ที่อัปโหลดแล้ว</a></div><?php endif ?><input id="resume_file" class="form-control" type="file" name="resume_file" accept=".pdf,.doc,.docx">
                    <div class="form-text">PDF, DOC หรือ DOCX</div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4"><button class="btn btn-success px-4" type="submit">บันทึกโปรไฟล์</button></div>
        </div>
    </form>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
