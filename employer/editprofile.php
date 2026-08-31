<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$employerId = user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        if ($firstName === '' || $lastName === '' || $companyName === '') {
            throw new RuntimeException('กรุณากรอกชื่อ นามสกุล และชื่อบริษัท / ผู้ว่าจ้าง');
        }

        $logo = upload_file('company_logo', ['jpg', 'jpeg', 'png', 'webp'], 'company-logos');
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET first_name=?, last_name=?, phone=? WHERE user_id=?')->execute([$firstName, $lastName, trim($_POST['phone'] ?? ''), $employerId]);
        $pdo->prepare('UPDATE employer_profiles SET company_name=?, company_description=?, company_address=?, company_logo_path=COALESCE(?, company_logo_path) WHERE user_id=?')->execute([
            $companyName, trim($_POST['company_description'] ?? ''), trim($_POST['company_address'] ?? ''), $logo, $employerId,
        ]);
        $pdo->commit();
        $_SESSION['user']['name'] = $firstName . ' ' . $lastName;
        flash('success', 'บันทึกข้อมูลส่วนตัวและข้อมูลบริษัทแล้ว');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    }
    redirect('employer/editprofile.php');
}

$profileStmt = $pdo->prepare('SELECT u.first_name, u.last_name, u.email, u.phone, ep.company_name, ep.company_description, ep.company_address, ep.company_logo_path FROM users u JOIN employer_profiles ep ON ep.user_id=u.user_id WHERE u.user_id=?');
$profileStmt->execute([$employerId]);
$profile = $profileStmt->fetch();

$pageTitle = 'แก้ไขข้อมูลผู้ว่าจ้าง | FLEXJOB';
$pageStyles = ['employer-editprofile'];
require APP_ROOT . '/partials/header.php';
?>

<main class="employer-profile-page">
    <div class="container py-4 py-lg-5">
        <header class="employer-profile-hero mb-4">
            <div class="employer-profile-identity">
                <div class="employer-profile-logo" aria-hidden="<?= !empty($profile['company_logo_path']) ? 'false' : 'true' ?>">
                    <?php if (!empty($profile['company_logo_path'])): ?>
                        <img src="<?= BASE_URL . '/' . e($profile['company_logo_path']) ?>" alt="โลโก้ <?= e($profile['company_name']) ?>" width="96" height="96">
                    <?php else: ?>
                        <span><?= e(mb_strtoupper(mb_substr($profile['company_name'] ?: 'F', 0, 1))) ?></span>
                    <?php endif ?>
                </div>
                <div>
                    <p class="employer-profile-eyebrow mb-1">EMPLOYER PROFILE</p>
                    <h1 class="mb-1"><?= e($profile['company_name'] ?: 'ข้อมูลบริษัทของคุณ') ?></h1>
                    <p class="mb-0 text-secondary">ดูแลข้อมูลที่ผู้สมัครจะเห็นเมื่อพบประกาศงานของคุณ</p>
                </div>
            </div>
            <a class="btn btn-outline-primary employer-profile-back" href="<?= BASE_URL ?>/employer/dashboard.php"><span aria-hidden="true">←</span> กลับสู่แดชบอร์ด</a>
        </header>

        <form method="post" enctype="multipart/form-data" class="employer-profile-form">
            <?= csrf_field() ?>
            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <section class="card border-0 shadow-sm employer-profile-section mb-4">
                        <div class="card-body p-4 p-lg-5">
                            <div class="employer-profile-section-heading">
                                <span class="employer-profile-step">01</span>
                                <div><h2 class="h4 mb-1">ข้อมูลผู้ติดต่อ</h2><p class="mb-0 text-secondary">ใช้สำหรับติดต่อและแสดงความน่าเชื่อถือของผู้ว่าจ้าง</p></div>
                            </div>
                            <fieldset class="border-0 p-0 m-0">
                                <legend class="visually-hidden">ข้อมูลผู้ติดต่อ</legend>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label" for="first_name">ชื่อ <span class="text-danger">*</span></label><input id="first_name" class="form-control" name="first_name" value="<?= e($profile['first_name'] ?? '') ?>" autocomplete="given-name" required></div>
                                    <div class="col-md-6"><label class="form-label" for="last_name">นามสกุล <span class="text-danger">*</span></label><input id="last_name" class="form-control" name="last_name" value="<?= e($profile['last_name'] ?? '') ?>" autocomplete="family-name" required></div>
                                    <div class="col-md-6"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" name="phone" value="<?= e($profile['phone'] ?? '') ?>" autocomplete="tel" inputmode="tel" placeholder="เช่น 081-234-5678"></div>
                                    <div class="col-md-6"><label class="form-label" for="email">อีเมลบัญชี</label><input id="email" class="form-control" value="<?= e($profile['email'] ?? '') ?>" type="email" autocomplete="email" readonly aria-describedby="emailHelp"><div id="emailHelp" class="form-text">อีเมลนี้เชื่อมกับบัญชี จึงแก้ไขจากหน้านี้ไม่ได้</div></div>
                                </div>
                            </fieldset>
                        </div>
                    </section>

                    <section class="card border-0 shadow-sm employer-profile-section">
                        <div class="card-body p-4 p-lg-5">
                            <div class="employer-profile-section-heading">
                                <span class="employer-profile-step">02</span>
                                <div><h2 class="h4 mb-1">ข้อมูลบริษัท / ผู้ว่าจ้าง</h2><p class="mb-0 text-secondary">ข้อมูลนี้จะแสดงคู่กับประกาศงาน เพื่อให้ผู้สมัครตัดสินใจได้ง่ายขึ้น</p></div>
                            </div>
                            <fieldset class="border-0 p-0 m-0">
                                <legend class="visually-hidden">ข้อมูลบริษัท</legend>
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label" for="company_name">ชื่อบริษัท / ผู้ว่าจ้าง <span class="text-danger">*</span></label><input id="company_name" class="form-control" name="company_name" value="<?= e($profile['company_name'] ?? '') ?>" autocomplete="organization" required></div>
                                    <div class="col-12"><label class="form-label" for="company_description">เกี่ยวกับบริษัท</label><textarea id="company_description" class="form-control" name="company_description" rows="5" maxlength="3000" placeholder="แนะนำธุรกิจ บริการ หรือรายละเอียดที่อยากให้ผู้สมัครทราบ"><?= e($profile['company_description'] ?? '') ?></textarea></div>
                                    <div class="col-12"><label class="form-label" for="company_address">ที่อยู่บริษัท</label><textarea id="company_address" class="form-control" name="company_address" rows="3" autocomplete="street-address" placeholder="ระบุที่อยู่หรือจุดประสานงานในบุรีรัมย์"><?= e($profile['company_address'] ?? '') ?></textarea></div>
                                </div>
                            </fieldset>
                        </div>
                    </section>
                </div>

                <aside class="col-lg-4">
                    <section class="card border-0 shadow-sm employer-logo-card">
                        <div class="card-body p-4">
                            <p class="employer-profile-eyebrow mb-2">COMPANY LOGO</p>
                            <h2 class="h5 mb-2">โลโก้บริษัท</h2>
                            <p class="small text-secondary mb-4">ใช้ในประกาศงานและข้อมูลบริษัทของคุณ</p>
                            <div class="employer-logo-preview">
                                <?php if (!empty($profile['company_logo_path'])): ?>
                                    <img src="<?= BASE_URL . '/' . e($profile['company_logo_path']) ?>" alt="โลโก้ปัจจุบันของ <?= e($profile['company_name']) ?>" width="160" height="160">
                                <?php else: ?>
                                    <span aria-hidden="true"><?= e(mb_strtoupper(mb_substr($profile['company_name'] ?: 'F', 0, 1))) ?></span>
                                    <small>ยังไม่ได้อัปโหลดโลโก้</small>
                                <?php endif ?>
                            </div>
                            <label class="form-label mt-4" for="company_logo">อัปโหลดโลโก้ใหม่</label>
                            <input id="company_logo" class="form-control" type="file" name="company_logo" accept=".jpg,.jpeg,.png,.webp" aria-describedby="logoHelp">
                            <div id="logoHelp" class="form-text">รองรับ JPG, PNG หรือ WEBP</div>
                        </div>
                    </section>
                    <section class="employer-profile-tip mt-3">
                        <strong>เคล็ดลับ</strong>
                        <span>ข้อมูลและโลโก้ที่ชัดเจนช่วยให้ผู้สมัครรู้จักบริษัทและตัดสินใจสมัครงานได้เร็วขึ้น</span>
                    </section>
                </aside>
            </div>

            <div class="employer-profile-save-bar mt-4">
                <p class="small text-secondary mb-0">ตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก</p>
                <button class="btn btn-primary employer-profile-save" type="submit">บันทึกข้อมูล <span aria-hidden="true">→</span></button>
            </div>
        </form>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
