<?php
require_once __DIR__ . '/config/config.php';
require_login('worker');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('jobs.php');
try {
    verify_csrf();
} catch (RuntimeException $e) {
    flash('error', $e->getMessage());
    redirect('jobs.php');
}
$jobId = (int)$_POST['job_id'];
$check = db()->prepare("SELECT job_id FROM jobs WHERE job_id=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
$check->execute([$jobId]);
if (!$check->fetch()) {
    flash('error', 'งานนี้ไม่เปิดรับสมัครแล้ว');
    redirect('jobs.php');
}
$profile = db()->prepare('SELECT resume_file_path FROM worker_profiles WHERE user_id=?');
$profile->execute([user()['id']]);
try {
    db()->prepare('INSERT INTO applications (job_id,worker_user_id,cover_note,resume_file_path) VALUES (?,?,?,?)')->execute([$jobId, user()['id'], trim($_POST['cover_note']), $profile->fetchColumn()]);
    $appId = (int)db()->lastInsertId();
    db()->prepare("UPDATE job_invitations SET invitation_status='accepted',responded_at=COALESCE(responded_at,NOW()) WHERE job_id=? AND worker_user_id=? AND invitation_status IN ('sent','viewed')")->execute([$jobId, user()['id']]);
    notify_employer_new_applicant($appId);
    flash('success', 'ส่งใบสมัครเรียบร้อยแล้ว ผู้ว่าจ้างจะติดต่อกลับผ่านระบบ');
} catch (PDOException) {
    flash('error', 'คุณสมัครงานนี้ไปแล้ว');
}
redirect('worker/dashboard.php');
