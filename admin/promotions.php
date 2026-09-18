<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

// เส้นทางเดิมคงไว้สำหรับบุ๊กมาร์กเก่า หลังยกเลิกการขายโปรโมตแยก
redirect('admin/reports.php');
