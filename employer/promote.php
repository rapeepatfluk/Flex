<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

// ลิงก์เก่าถูกเก็บไว้เพื่อให้บุ๊กมาร์กและการแจ้งเตือนเดิมยังใช้งานได้
$jobId = (int) ($_GET['job'] ?? $_POST['job_id'] ?? 0);
redirect('employer/subscription.php' . ($jobId > 0 ? '?job=' . $jobId : ''));
