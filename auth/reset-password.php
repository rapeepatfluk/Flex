<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$u = null;
$errorMsg = '';

if ($token === '') {
    $errorMsg = 'ไม่พบรหัสการตั้งค่าผ่านทางลิงก์นี้';
} else {
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT r.*, u.first_name, u.last_name, u.email
         FROM auth_tokens r
         JOIN users u ON u.user_id = r.user_id
         WHERE r.token = ?
           AND r.token_type = 'password_reset'
           AND r.used_at IS NULL
           AND r.expires_at >= NOW()
         LIMIT 1"
    );
    $stmt->execute([$token]);
    $u = $stmt->fetch();

    if (!$u) {
        $errorMsg = 'ลิงก์ตั้งรหัสผ่านใหม่ไม่ถูกต้องหรือหมดอายุแล้ว (ลิงก์มีอายุ 1 ชั่วโมง)';
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errorMsg) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
    }

    if (empty($errors)) {
        // อัปเดตรหัสผ่านในตาราง users
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
            ->execute([$passwordHash, $u['user_id']]);

        // ทำเครื่องหมายว่า Token นี้ถูกใช้แล้ว
        $pdo->prepare('UPDATE auth_tokens SET used_at = NOW() WHERE auth_token_id = ?')
            ->execute([$u['auth_token_id']]);

        flash('success', 'ตั้งรหัสผ่านใหม่สำเร็จแล้ว! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่');
        redirect('auth/login.php');
    }
}

$pageTitle = 'ตั้งรหัสผ่านใหม่ | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="form-card" style="max-width:500px">
        <p class="eyebrow">NEW PASSWORD</p>
        <h1>ตั้งรหัสผ่านใหม่</h1>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger" style="margin: 20px 0;">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
            <p class="form-note" style="margin-top:20px;font-size:13px">
                <a href="<?= BASE_URL ?>/auth/forgot-password.php">← ขอลิงก์กู้คืนรหัสผ่านใหม่</a>
            </p>
        <?php else: ?>
            <p>สวัสดีคุณ <strong><?= htmlspecialchars(trim($u['first_name'] . ' ' . $u['last_name'])) ?></strong> (<?= htmlspecialchars($u['email']) ?>) <br>กรุณาระบุรหัสผ่านใหม่ของคุณด้านล่าง</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <label for="password">รหัสผ่านใหม่</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="อย่างน้อย 8 ตัวอักษร">

                <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                <input id="confirm_password" type="password" name="confirm_password" required autocomplete="new-password" placeholder="กรอกรหัสผ่านอีกครั้ง">

                <button class="btn btn-primary full-width" style="margin-top:20px" type="submit">บันทึกรหัสผ่านใหม่</button>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>