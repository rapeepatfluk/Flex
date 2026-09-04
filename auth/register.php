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
            // Only accounts created from this point forward are required to complete onboarding.
            // Existing Worker accounts remain unaffected by the new flow.
            $pdo->prepare('INSERT INTO worker_profiles (user_id,matching_survey_required_at) VALUES (?,NOW())')->execute([$id]);
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
$pageStyles = ['auth'];
$pageScripts = ['auth'];
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page auth-experience py-4 py-lg-5">
    <div class="container">
        <div class="auth-shell auth-shell-register row g-0 mx-auto overflow-hidden">
            <aside class="auth-aside col-lg-5 d-none d-lg-flex flex-column" aria-label="สิทธิประโยชน์ FLEXJOB">
                <div class="auth-aside-glow auth-aside-glow-one"></div><div class="auth-aside-glow auth-aside-glow-two"></div>
                <div class="position-relative">
                    <p class="auth-kicker mb-3">JOIN FLEXJOB</p>
                    <h2>เริ่มต้นโอกาสใหม่<br>ในแบบของคุณ</h2>
                    <p class="mb-0">สร้างบัญชีเพียงไม่กี่ขั้นตอน แล้วเริ่มหางานหรือประกาศงานได้ทันทีหลังยืนยันอีเมล</p>
                </div>
                <div class="auth-benefits mt-auto position-relative">
                    <div><span aria-hidden="true">1</span><p><b>สร้างบัญชี</b><small>เลือกบทบาทที่ตรงกับคุณ</small></p></div>
                    <div><span aria-hidden="true">2</span><p><b>ยืนยันอีเมล</b><small>เพื่อความปลอดภัยของบัญชี</small></p></div>
                    <div><span aria-hidden="true">3</span><p><b>เริ่มค้นหาโอกาส</b><small>ทุกอย่างพร้อมใน FLEXJOB</small></p></div>
                </div>
            </aside>

            <section class="auth-content col-lg-7">
                <div class="auth-form-wrap auth-form-register-wrap">
                    <a class="auth-back-link" href="<?= BASE_URL ?>/index.php"><span aria-hidden="true">←</span> กลับหน้าแรก</a>
                    <div class="auth-mobile-mark d-lg-none" aria-hidden="true"><span>F</span></div>
                    <p class="eyebrow mb-2">CREATE YOUR ACCOUNT</p>
                    <h1 class="auth-title">สร้างบัญชีใหม่</h1>
                    <p class="auth-subtitle">เลือกบทบาทของคุณ แล้วกรอกข้อมูลเพื่อเริ่มใช้งาน FLEXJOB</p>

                    <form method="post" class="auth-form">
                        <?= csrf_field() ?>
                        <fieldset class="mb-4"><legend class="form-label mb-2">คุณต้องการใช้งานในฐานะ</legend>
                            <div class="auth-role-grid" role="radiogroup" aria-label="บทบาทผู้ใช้งาน">
                                <input class="btn-check" type="radio" name="role" value="worker" id="role-worker" checked>
                                <label class="auth-role-card" for="role-worker"><span class="auth-role-icon" aria-hidden="true">⌕</span><span><b>ผู้หางาน</b><small>ค้นหาและสมัครงานที่สนใจ</small></span></label>
                                <input class="btn-check" type="radio" name="role" value="employer" id="role-employer">
                                <label class="auth-role-card" for="role-employer"><span class="auth-role-icon" aria-hidden="true">＋</span><span><b>ผู้ว่าจ้าง</b><small>ประกาศงานและคัดเลือกผู้สมัคร</small></span></label>
                            </div>
                        </fieldset>

                        <div class="auth-company-field mb-3" data-company-field hidden>
                            <label class="form-label" for="company_name">ชื่อบริษัท / ผู้ว่าจ้าง</label>
                            <input class="form-control form-control-lg" id="company_name" type="text" name="company_name" autocomplete="organization" placeholder="เช่น ABC Company">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6"><label class="form-label" for="first_name">ชื่อ</label><input class="form-control form-control-lg" id="first_name" type="text" name="first_name" required autocomplete="given-name" enterkeyhint="next"></div>
                            <div class="col-sm-6"><label class="form-label" for="last_name">นามสกุล</label><input class="form-control form-control-lg" id="last_name" type="text" name="last_name" required autocomplete="family-name" enterkeyhint="next"></div>
                        </div>
                        <div class="mb-3"><label class="form-label" for="email">อีเมล</label><input class="form-control form-control-lg" id="email" type="email" name="email" required autocomplete="username" inputmode="email" enterkeyhint="next" placeholder="name@example.com"></div>
                        <div class="mb-3"><label class="form-label" for="phone">เบอร์โทรศัพท์</label><input class="form-control form-control-lg" id="phone" type="tel" name="phone" autocomplete="tel" inputmode="tel" enterkeyhint="next" placeholder="08x-xxx-xxxx"></div>
                        <div class="mb-2"><label class="form-label" for="new-password">รหัสผ่าน</label><div class="password-control"><input class="form-control form-control-lg" id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password" enterkeyhint="done" aria-describedby="password-help" placeholder="อย่างน้อย 8 ตัวอักษร"><button class="password-toggle" type="button" data-password-toggle aria-controls="new-password" aria-pressed="false"><span>แสดง</span><span class="visually-hidden">รหัสผ่าน</span></button></div><div class="form-text" id="password-help">ใช้รหัสผ่านอย่างน้อย 8 ตัวอักษร</div></div>
                        <button class="btn btn-primary btn-lg w-100 auth-submit" type="submit">สร้างบัญชีและยืนยันอีเมล <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="auth-switch mb-0">มีบัญชีอยู่แล้ว? <a href="<?= BASE_URL ?>/auth/login.php">เข้าสู่ระบบ</a></p>
                </div>
            </section>
        </div>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
