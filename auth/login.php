<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $stmt  = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $found = $stmt->fetch();

    if ($found && password_verify($_POST['password'], $found['password_hash'])) {
        if ($found['account_status'] === 'pending') {
            $_SESSION['pending_verify'] = ['email' => $found['email'], 'name' => trim($found['first_name'] . ' ' . $found['last_name'])];
            flash('error', 'กรุณายืนยันอีเมลก่อนเข้าสู่ระบบ');
            redirect('auth/pending-verify.php');
        } elseif ($found['account_status'] === 'suspended') {
            flash('error', 'บัญชีนี้ถูกระงับการใช้งาน');
        } else {
            $_SESSION['user'] = [
                'id'    => $found['user_id'],
                'name'  => trim($found['first_name'] . ' ' . $found['last_name']),
                'role'  => $found['role'],
                'email' => $found['email'],
            ];
            redirect(dashboard_path($found['role']));
        }
    } else {
        flash('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
    }
}
$pageTitle = 'เข้าสู่ระบบ | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="form-card">
        <p class="eyebrow">WELCOME BACK</p>
        <h1>เข้าสู่ระบบ</h1>
        <p>เข้าสู่ FLEXJOB เพื่อจัดการงานของคุณ</p>

        <form method="post">
            <label for="email">อีเมล</label>
            <input id="email" type="email" name="email" required autocomplete="email" placeholder="your@email.com">

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                <label for="password" style="margin-bottom:0">รหัสผ่าน</label>
                
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            <a href="<?= BASE_URL ?>/auth/forgot-password.php" style="font-size:13px; text-decoration:none;">ลืมรหัสผ่าน?</a>
            <button class="btn btn-primary full-width" style="margin-top:20px" type="submit">เข้าสู่ระบบ</button>
        </form>

        <p class="form-note" style="margin-top:16px;font-size:13px">
            ยังไม่มีบัญชี? <a href="<?= BASE_URL ?>/auth/register.php"><b>สมัครใช้งาน</b></a>
        </p>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>