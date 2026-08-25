<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('employer/dashboard.php');
}
try { verify_csrf(); } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('employer/dashboard.php'); }

$statement = db()->prepare('DELETE FROM jobs WHERE job_id=? AND employer_user_id=?');
$statement->execute([(int) ($_POST['job_id'] ?? 0), user()['id']]);

flash($statement->rowCount() ? 'success' : 'error', $statement->rowCount() ? 'ลบประกาศงานแล้ว' : 'ไม่พบประกาศงานที่ต้องการลบ');
redirect('employer/dashboard.php');
