<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('employer/dashboard.php');
}
try { verify_csrf(); } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('employer/dashboard.php'); }

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
try {
    $pdo->beginTransaction();
    $jobStatement = $pdo->prepare('SELECT job_id FROM jobs WHERE job_id=? AND employer_user_id=? FOR UPDATE');
    $jobStatement->execute([$jobId, user()['id']]);
    if (!$jobStatement->fetchColumn()) throw new RuntimeException('ไม่พบประกาศงานที่ต้องการลบ');

    $historyStatement = $pdo->prepare('SELECT
        EXISTS(SELECT 1 FROM applications WHERE job_id=?)
        OR EXISTS(SELECT 1 FROM job_invitations WHERE job_id=?)
        OR EXISTS(SELECT 1 FROM job_promotions WHERE job_id=?)');
    $historyStatement->execute([$jobId, $jobId, $jobId]);
    if ($historyStatement->fetchColumn()) {
        throw new RuntimeException('ประกาศนี้มีประวัติผู้สมัคร คำเชิญ หรือการโปรโมต จึงลบถาวรไม่ได้ กรุณาใช้คำสั่งซ่อนแทน');
    }

    $pdo->prepare('DELETE FROM jobs WHERE job_id=? AND employer_user_id=?')->execute([$jobId, user()['id']]);
    $pdo->commit();
    flash('success', 'ลบประกาศงานแล้ว');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $e->getMessage());
}
redirect('employer/dashboard.php');
