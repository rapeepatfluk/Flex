<?php
require_once __DIR__ . '/../config/config.php';
if (user()) redirect(dashboard_path(user()['role']));

$token = trim($_GET['token'] ?? '');

if (!$token) {
    flash('error', 'ลิงก์ยืนยันไม่ถูกต้อง');
    redirect('auth/login.php');
}

$pdo = db();
$s = $pdo->prepare('SELECT ev.*, u.email, CONCAT(u.first_name," ",u.last_name) AS name, u.role FROM email_verifications ev JOIN users u ON u.user_id = ev.user_id WHERE ev.token = ?');
$s->execute([$token]);
$row = $s->fetch();

if (!$row) {
    flash('error', 'ลิงก์ยืนยันไม่ถูกต้องหรือถูกใช้ไปแล้ว');
    redirect('auth/login.php');
}

if ($row['used_at']) {
    flash('success', 'อีเมลถูกยืนยันแล้ว สามารถเข้าสู่ระบบได้เลย');
    redirect('auth/login.php');
}

if (strtotime($row['expires_at']) < time()) {
    flash('error', 'ลิงก์ยืนยันหมดอายุแล้ว (24 ชั่วโมง) — กรุณาขอลิงก์ใหม่');
    redirect('auth/resend-verify.php');
}

// Mark token as used & activate account
$pdo->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
$pdo->prepare("UPDATE users SET account_status='active', email_verified_at=NOW() WHERE user_id=?")->execute([$row['user_id']]);

unset($_SESSION['pending_verify']);
flash('success', '🎉 ยืนยันอีเมลสำเร็จ! สามารถเข้าสู่ระบบได้เลย');
redirect('auth/login.php');
