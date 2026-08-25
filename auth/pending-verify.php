<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

$pendingEmail = $_SESSION['pending_verify']['email'] ?? '';
$pendingName  = $_SESSION['pending_verify']['name'] ?? '';

// Handle resend form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (!mail_is_configured()) throw new RuntimeException('ระบบอีเมลยังไม่ได้ตั้งค่า กรุณาติดต่อผู้ดูแลระบบ');
        $email = strtolower(trim($_POST['email'] ?? $pendingEmail));
        $pdo   = db();
        $s     = $pdo->prepare("SELECT user_id, CONCAT(first_name,' ',last_name) AS name, account_status FROM users WHERE email=?");
        $s->execute([$email]);
        $u = $s->fetch();

        if (!$u || $u['account_status'] !== 'pending') throw new RuntimeException('ไม่พบบัญชีที่รอการยืนยัน หรืออีเมลนี้ยืนยันแล้ว');

        $token = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO email_verifications (user_id,token,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 24 HOUR))')
            ->execute([$u['user_id'], $token]);
        $verificationId = (int) $pdo->lastInsertId();
        $verifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/auth/verify.php?token=' . $token;
        $body = <<<HTML
<h2 style="margin:0 0 8px;font-size:22px;color:#17231f;">ส่งลิงก์ยืนยันใหม่แล้ว</h2>
<p style="margin:0 0 20px;color:#697671;">สวัสดี {$u['name']} — ลิงก์ยืนยันอีเมลของคุณมาแล้ว!</p>
HTML;
        $body .= email_btn($verifyUrl, '✅ ยืนยันอีเมลของฉัน');

        if (send_mail($email, $u['name'], 'ลิงก์ยืนยันอีเมล (ใหม่) — FLEXJOB', $body)) {
            $pdo->prepare('UPDATE email_verifications SET used_at=NOW() WHERE user_id=? AND id<>? AND used_at IS NULL')->execute([$u['user_id'], $verificationId]);
            flash('success', 'ส่งลิงก์ยืนยันใหม่ไปที่ ' . $email . ' แล้ว');
        } else {
            $pdo->prepare('UPDATE email_verifications SET used_at=NOW() WHERE id=?')->execute([$verificationId]);
            throw new RuntimeException('ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบการตั้งค่า SMTP หรือลองใหม่ภายหลัง');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$pageTitle = 'รอการยืนยันอีเมล | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="auth-page">
    <div class="form-card" style="text-align:center;max-width:500px">

        <!-- Email icon -->
        <div style="width:72px;height:72px;border-radius:50%;background:#e9f6ef;display:grid;place-items:center;margin:0 auto 20px;font-size:34px">📧</div>

        <p class="eyebrow">ตรวจสอบอีเมลของคุณ</p>
        <h1 style="font-size:26px">รอการยืนยันอีเมล</h1>

        <?php if ($pendingEmail): ?>
        <p style="color:var(--muted);font-size:14px;margin:8px 0 24px">
            บัญชีที่รอการยืนยัน:<br>
            <strong style="color:var(--ink)"><?= e($pendingEmail) ?></strong>
        </p>
        <?php else: ?>
        <p style="color:var(--muted);font-size:14px;margin:8px 0 24px">กรุณาตรวจสอบกล่องจดหมายของคุณ และคลิกลิงก์ยืนยัน</p>
        <?php endif; ?>

        <div style="background:#f0faf4;border:1px solid #b8dfc8;border-radius:12px;padding:16px;margin-bottom:24px;text-align:left">
            <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#17231f">💡 ไม่เจออีเมล?</p>
            <ul style="margin:0;padding-left:18px;font-size:13px;color:var(--muted);line-height:1.8">
                <li>ตรวจสอบโฟลเดอร์ Spam / Junk Mail</li>
                <li>ลิงก์จะหมดอายุใน 24 ชั่วโมง</li>
                <li>กรอกอีเมลด้านล่างเพื่อขอลิงก์ใหม่</li>
            </ul>
        </div>

        <?php if (!mail_is_configured()): ?><div class="alert alert-danger text-start small">ระบบยังไม่พร้อมส่งอีเมล กรุณาให้ผู้ดูแลตั้งค่า SMTP ก่อนกดส่งใหม่</div><?php endif ?>
        <form method="post" style="text-align:left">
            <?= csrf_field() ?>
            <label for="resend_email">ขอลิงก์ยืนยันใหม่</label>
            <input id="resend_email" type="email" name="email" value="<?= e($pendingEmail) ?>" placeholder="your@email.com" required>
            <button class="btn btn-primary full-width" style="margin-top:12px" type="submit" <?= mail_is_configured() ? '' : 'disabled' ?>>ส่งลิงก์ใหม่</button>
        </form>

        <p class="form-note" style="margin-top:20px;font-size:13px">
            <a href="<?= BASE_URL ?>/auth/login.php">← กลับหน้าเข้าสู่ระบบ</a>
        </p>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
