<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try { verify_csrf(); } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('auth/forgot-password.php'); }
    $email = strtolower(trim($_POST['email'] ?? ''));
    
    if ($email === '') {
        flash('error', 'กรุณากรอกอีเมลของคุณ');
    } else {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT user_id, CONCAT(first_name, ' ', last_name) AS name, account_status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u) {
            if ($u['account_status'] === 'suspended') {
                flash('error', 'บัญชีนี้ถูกระงับการใช้งาน');
            } else {
                // สร้าง Token ใหม่สำหรับการรีเซ็ต (มีอายุ 1 ชั่วโมง)
                $token = bin2hex(random_bytes(32));
                $pdo->prepare("INSERT INTO auth_tokens (user_id,token,token_type,expires_at) VALUES (?,?,'password_reset',DATE_ADD(NOW(), INTERVAL 1 HOUR))")
                    ->execute([$u['user_id'], $token]);
                $resetId = (int) $pdo->lastInsertId();

                // ลิงก์กู้คืน
                $resetUrl = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/reset-password.php?token=' . $token;
                
                // รูปแบบเนื้อหาอีเมล
                $body = <<<HTML
<h2 style="margin:0 0 8px;font-size:22px;color:#17231f;">คำขอกู้คืนรหัสผ่าน</h2>
<p style="margin:0 0 20px;color:#697671;">สวัสดี {$u['name']} — ระบบได้รับคำขอกู้คืนรหัสผ่านของบัญชี FLEXJOB กรุณาคลิกปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่ ลิงก์นี้จะหมดอายุภายใน 1 ชั่วโมง:</p>
HTML;
                $body .= email_btn($resetUrl, '🔑 ตั้งรหัสผ่านใหม่');

                if (send_mail($email, $u['name'], 'ตั้งรหัสผ่านใหม่สำหรับบัญชี FLEXJOB', $body)) {
                    $pdo->prepare("UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND auth_token_id<>? AND token_type='password_reset' AND used_at IS NULL")->execute([$u['user_id'], $resetId]);
                    flash('success', 'ส่งลิงก์สำหรับตั้งรหัสผ่านใหม่ไปยัง ' . $email . ' เรียบร้อยแล้ว กรุณาตรวจสอบกล่องจดหมายของคุณ');
                } else {
                    $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE auth_token_id=?')->execute([$resetId]);
                    flash('error', 'ไม่สามารถส่งอีเมลได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง');
                }
            }
        } else {
            flash('error', 'ไม่พบอีเมลนี้ในระบบ');
        }
    }
}

$pageTitle = 'ลืมรหัสผ่าน | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="form-card" style="max-width:500px">
        <p class="eyebrow">RESET PASSWORD</p>
        <h1>ลืมรหัสผ่าน</h1>
        <p>ระบุอีเมลของคุณที่ใช้สมัคร เพื่อรับลิงก์สำหรับตั้งรหัสผ่านใหม่</p>

        <form method="post">
            <?= csrf_field() ?>
            <label for="email">อีเมล</label>
            <input id="email" type="email" name="email" required autocomplete="email" placeholder="your@email.com">
            <button class="btn btn-primary full-width" style="margin-top:20px" type="submit">ส่งลิงก์กู้คืนรหัสผ่าน</button>
        </form>

        <p class="form-note" style="margin-top:20px;font-size:13px">
            <a href="<?= BASE_URL ?>/auth/login.php">← กลับหน้าเข้าสู่ระบบ</a>
        </p>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
