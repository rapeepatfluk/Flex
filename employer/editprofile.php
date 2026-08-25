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

        $pdo->prepare('UPDATE users SET first_name=?, last_name=?, phone=? WHERE user_id=?')
            ->execute([$firstName, $lastName, trim($_POST['phone'] ?? ''), $employerId]);

        $pdo->prepare('UPDATE employer_profiles SET company_name=?, company_description=?, company_address=?, company_logo_path=COALESCE(?, company_logo_path) WHERE user_id=?')
            ->execute([
                $companyName,
                trim($_POST['company_description'] ?? ''),
                trim($_POST['company_address'] ?? ''),
                $logo,
                $employerId,
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
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">EMPLOYER PROFILE</p>
            <h1 class="h2 mb-1">แก้ไขข้อมูลส่วนตัวและบริษัท</h1>
            <p class="text-secondary mb-0">โลโก้บริษัทจะแสดงคู่กับประกาศงานของคุณ</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/employer/index.php">กลับ</a>
    </div>

    <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body p-4 p-md-5">
            <h2 class="h5 mb-3">ข้อมูลส่วนตัว</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label" for="first_name">ชื่อ</label><input id="first_name" class="form-control" name="first_name" value="<?= e($profile['first_name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label" for="last_name">นามสกุล</label><input id="last_name" class="form-control" name="last_name" value="<?= e($profile['last_name'] ?? '') ?>" required></div>
                <div class="col-md-6"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input id="phone" class="form-control" name="phone" value="<?= e($profile['phone'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label" for="email">อีเมล</label><input id="email" class="form-control" value="<?= e($profile['email'] ?? '') ?>" disabled></div>
            </div>

            <h2 class="h5 mb-3">ข้อมูลบริษัท / ผู้ว่าจ้าง</h2>
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label" for="company_name">ชื่อบริษัท / ผู้ว่าจ้าง</label><input id="company_name" class="form-control" name="company_name" value="<?= e($profile['company_name'] ?? '') ?>" required></div>
                <div class="col-md-4"><label class="form-label" for="company_logo">โลโก้บริษัท</label><input id="company_logo" class="form-control" type="file" name="company_logo" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">JPG, PNG หรือ WEBP</div></div>
                <div class="col-12"><label class="form-label" for="company_description">เกี่ยวกับบริษัท</label><textarea id="company_description" class="form-control" name="company_description" rows="4" placeholder="แนะนำธุรกิจหรือรายละเอียดที่อยากให้ผู้สมัครทราบ"><?= e($profile['company_description'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label" for="company_address">ที่อยู่บริษัท</label><input id="company_address" class="form-control" name="company_address" value="<?= e($profile['company_address'] ?? '') ?>"></div>
                <?php if (!empty($profile['company_logo_path'])): ?>
                    <div class="col-12 d-flex align-items-center gap-3"><img class="rounded-circle border object-fit-cover" src="<?= BASE_URL . '/' . e($profile['company_logo_path']) ?>" alt="โลโก้ <?= e($profile['company_name']) ?>" width="72" height="72"><span class="text-secondary small">โลโก้ปัจจุบันของคุณ</span></div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-end mt-4"><button class="btn btn-success px-4" type="submit">บันทึกข้อมูล</button></div>
        </div>
    </form>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
