<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try { verify_csrf(); } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('auth/register.php'); }
    $role        = $_POST['role'] === 'employer' ? 'employer' : 'worker';
    $firstName   = trim($_POST['first_name']);
    $lastName    = trim($_POST['last_name']);
    $companyName = trim($_POST['company_name'] ?? '');
    $email       = strtolower(trim($_POST['email']));
    $phone       = trim($_POST['phone']);
    $password    = $_POST['password'];

    try {
        if ($role === 'employer' && $companyName === '') {
            throw new RuntimeException('กรุณากรอกชื่อบริษัทหรือชื่อผู้ว่าจ้าง');
        }

        $pdo = db();
        $pdo->beginTransaction();

        // Insert user with 'pending' status
        $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,role,phone,account_status) VALUES (?,?,?,?,?,?,\'pending\')')
            ->execute([$firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone]);
        $id = (int)$pdo->lastInsertId();

        if ($role === 'employer') {
            $pdo->prepare('INSERT INTO employer_profiles (user_id,company_name) VALUES (?,?)')->execute([$id, $companyName]);
        } else {
            $pdo->prepare('INSERT INTO worker_profiles (user_id) VALUES (?)')->execute([$id]);
        }

        // Create verification token (expires in 24h)
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT INTO auth_tokens (user_id,token,token_type,expires_at) VALUES (?,?,'email_verification',DATE_ADD(NOW(), INTERVAL 24 HOUR))")
            ->execute([$id, $token]);

        $pdo->commit();

        // Send verification email
        $verifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/verify.php?token=' . $token;
        $fullName  = "$firstName $lastName";

        $emailBody = <<<HTML
<h2 style="margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;">ยินดีต้อนรับสู่ FLEXJOB! 🎉</h2>
<p style="margin:0 0 20px;color:#697671;font-size:15px;">สวัสดี {$fullName} — บัญชีของคุณถูกสร้างเรียบร้อยแล้ว กรุณายืนยันอีเมลเพื่อเริ่มใช้งาน</p>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8f4;border-radius:12px;margin-bottom:24px;">
  <tr><td style="padding:20px 24px;">
    <p style="margin:0 0 4px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;">ลิงก์ยืนยันอีเมลจะหมดอายุใน</p>
    <p style="margin:0;font-size:15px;font-weight:700;color:#17231f;">24 ชั่วโมง</p>
  </td></tr>
</table>
HTML;
        $emailBody .= email_btn($verifyUrl, '✅ ยืนยันอีเมลของฉัน');
        $emailBody .= '<p style="font-size:12px;color:#8a9e96;text-align:center;margin-top:16px;">หากปุ่มไม่ทำงาน คัดลอกลิงก์นี้: <br><a href="' . $verifyUrl . '" style="color:#166b54;">' . $verifyUrl . '</a></p>';

        $sent = send_mail($email, $fullName, 'ยืนยันอีเมลของคุณ — FLEXJOB', $emailBody);

        // Store minimal info in session for resend page
        $_SESSION['pending_verify'] = ['email' => $email, 'name' => $fullName];

        if ($sent) {
            flash('success', 'สมัครสมาชิกเรียบร้อย! กรุณาตรวจสอบอีเมลเพื่อยืนยันบัญชี');
        } else {
            flash('error', 'สร้างบัญชีแล้ว แต่ยังส่งอีเมลยืนยันไม่ได้ กรุณากดส่งใหม่หลังผู้ดูแลตั้งค่า SMTP');
        }
        redirect('auth/pending-verify.php');

    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        flash('error', str_contains($e->getMessage(), 'Duplicate') ? 'อีเมลนี้ถูกใช้งานแล้ว' : $e->getMessage());
    }
}

$pageTitle = 'สมัครใช้งาน | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="form-card">
        <p class="eyebrow">JOIN FLEXJOB</p>
        <h1>สร้างบัญชีใหม่</h1>
        <p>กรอกข้อมูลเพื่อสมัครใช้งาน FLEXJOB</p>

        <form method="post">
            <?= csrf_field() ?>
            <div class="role-choice">
                <label style="flex:1;cursor:pointer">
                    <input type="radio" name="role" value="worker" checked style="margin-right:6px">ผู้หางาน
                </label>
                <label style="flex:1;cursor:pointer">
                    <input type="radio" name="role" value="employer" style="margin-right:6px">ผู้ว่าจ้าง
                </label>
            </div>

            <div class="company-field" id="company-field" style="display:none">
                <label for="company_name">ชื่อบริษัท / ผู้ว่าจ้าง</label>
                <input id="company_name" type="text" name="company_name" placeholder="ABC Company">
            </div>

            <label for="first_name">ชื่อ</label>
            <input id="first_name" type="text" name="first_name" required autocomplete="given-name">

            <label for="last_name">นามสกุล</label>
            <input id="last_name" type="text" name="last_name" required autocomplete="family-name">

            <label for="email">อีเมล</label>
            <input id="email" type="email" name="email" required autocomplete="email" placeholder="example@email.com">

            <label for="phone">เบอร์โทรศัพท์</label>
            <input id="phone" type="tel" name="phone" autocomplete="tel" placeholder="08x-xxx-xxxx">

            <label for="password">รหัสผ่าน</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="อย่างน้อย 8 ตัวอักษร">

            <button class="btn btn-primary full-width" style="margin-top:20px" type="submit">สร้างบัญชี</button>
        </form>

        <p class="form-note" style="margin-top:16px;font-size:13px">มีบัญชีอยู่แล้ว? <a href="<?= BASE_URL ?>/auth/login.php"><b>เข้าสู่ระบบ</b></a></p>
    </div>
</main>
<script>
const companyField = document.getElementById('company-field');
const companyInput = companyField.querySelector('input');
const toggle = () => {
    const isEmployer = document.querySelector('input[name=role]:checked').value === 'employer';
    companyField.style.display = isEmployer ? 'block' : 'none';
    companyInput.required = isEmployer;
};
document.querySelectorAll('input[name=role]').forEach(r => r.addEventListener('change', toggle));
toggle();
</script>
<?php require APP_ROOT . '/partials/footer.php'; ?>
