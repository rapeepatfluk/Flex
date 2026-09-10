<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
        redirect('auth/login.php');
    }
    $attempts = (int) ($_SESSION['login_attempts']['count'] ?? 0);
    $lastAttempt = (int) ($_SESSION['login_attempts']['last_at'] ?? 0);
    if ($attempts >= 5 && $lastAttempt > time() - 300) {
        flash('error', 'ลองเข้าสู่ระบบหลายครั้งเกินไป กรุณารอ 5 นาทีแล้วลองใหม่');
        redirect('auth/login.php');
    }
    if ($lastAttempt <= time() - 300) $attempts = 0;
    $identifier = normalize_username((string) ($_POST['identifier'] ?? $_POST['email'] ?? ''));
    $stmt  = db()->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$identifier, $identifier]);
    $found = $stmt->fetch();

    if ($found && password_verify((string) ($_POST['password'] ?? ''), $found['password_hash'])) {
        unset($_SESSION['login_attempts']);
        if ($found['account_status'] === 'pending') {
            $_SESSION['pending_verify'] = ['email' => $found['email'], 'name' => trim($found['first_name'] . ' ' . $found['last_name'])];
            flash('error', 'กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ');
            redirect('auth/pending-verify.php');
        } elseif ($found['account_status'] === 'suspended') {
            flash('error', 'บัญชีนี้ถูกระงับการใช้งาน');
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $found['user_id'],
                'name'  => trim($found['first_name'] . ' ' . $found['last_name']),
                'role'  => $found['role'],
                'email' => $found['email'],
                'username' => $found['username'],
            ];
            redirect(dashboard_path($found['role']));
        }
    } else {
        $_SESSION['login_attempts'] = ['count' => $attempts + 1, 'last_at' => time()];
        flash('error', 'อีเมล ชื่อผู้ใช้ หรือรหัสผ่านไม่ถูกต้อง');
    }
}
$pageTitle = 'เข้าสู่ระบบ | FLEXJOB';
$pageStyles = ['auth'];
$pageScripts = ['auth'];
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page auth-experience py-4 py-lg-5">
    <div class="container">
        <div class="auth-shell row g-0 mx-auto overflow-hidden">
            <aside class="auth-aside col-lg-5 d-none d-lg-flex flex-column" aria-label="ข้อมูล FLEXJOB">
                <div class="auth-aside-glow auth-aside-glow-one"></div><div class="auth-aside-glow auth-aside-glow-two"></div>
                <div class="position-relative">
                    <p class="auth-kicker mb-3">FLEXJOB ACCOUNT</p>
                    <h2>งานที่ใช่<br>เริ่มต้นจากที่นี่</h2>
                    <p class="mb-0">เข้าสู่ระบบเพื่อค้นหางาน ติดตามใบสมัคร และจัดการทุกโอกาสของคุณในที่เดียว</p>
                </div>
                <div class="auth-benefits mt-auto position-relative">
                    <div><span aria-hidden="true">✓</span><p><b>ค้นหางานที่เปิดรับ</b><small>เลือกโอกาสที่เหมาะกับคุณ</small></p></div>
                    <div><span aria-hidden="true">✦</span><p><b>ติดตามทุกขั้นตอน</b><small>ดูสถานะใบสมัครได้ง่าย</small></p></div>
                    <div><span aria-hidden="true">★</span><p><b>สร้างความน่าเชื่อถือ</b><small>สะสมคะแนนรีวิวจากงานจริง</small></p></div>
                </div>
            </aside>

            <section class="auth-content col-lg-7">
                <div class="auth-form-wrap">
                    <a class="auth-back-link" href="<?= BASE_URL ?>/index.php"><span aria-hidden="true">←</span> กลับหน้าแรก</a>
                    <div class="auth-mobile-mark d-lg-none" aria-hidden="true"><span>F</span></div>
                    <p class="eyebrow mb-2">WELCOME BACK</p>
                    <h1 class="auth-title">ยินดีต้อนรับกลับ</h1>
                    <p class="auth-subtitle">เข้าสู่ FLEXJOB เพื่อจัดการงานของคุณ</p>

                    <form method="post" class="auth-form">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="identifier">อีเมลหรือชื่อผู้ใช้</label>
                            <input class="form-control form-control-lg" id="identifier" type="text" name="identifier" required autocomplete="username" autocapitalize="none" spellcheck="false" enterkeyhint="next" placeholder="name@example.com หรือ username">
                        </div>
                        <div class="mb-2">
                            <div class="d-flex align-items-center justify-content-between gap-3"><label class="form-label mb-0" for="current-password">รหัสผ่าน</label><a class="auth-inline-link" href="<?= BASE_URL ?>/auth/forgot-password.php">ลืมรหัสผ่าน?</a></div>
                            <div class="password-control mt-2"><input class="form-control form-control-lg" id="current-password" type="password" name="password" required autocomplete="current-password" enterkeyhint="done" placeholder="กรอกรหัสผ่าน"><button class="password-toggle" type="button" data-password-toggle aria-controls="current-password" aria-pressed="false"><span>แสดง</span><span class="visually-hidden">รหัสผ่าน</span></button></div>
                        </div>
                        <button class="btn btn-primary btn-lg w-100 auth-submit" type="submit">เข้าสู่ระบบ <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="auth-switch mb-0">ยังไม่มีบัญชี? <a href="<?= BASE_URL ?>/auth/register.php">สมัครใช้งานฟรี</a></p>
                </div>
            </section>
        </div>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
