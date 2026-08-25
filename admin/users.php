<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_login('admin');

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $userId = (int) ($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['active', 'suspended'], true)) throw new RuntimeException('สถานะบัญชีไม่ถูกต้อง');

        $accountStmt = $pdo->prepare("SELECT role FROM users WHERE user_id=? AND role IN ('worker', 'employer')");
        $accountStmt->execute([$userId]);
        if (!$accountStmt->fetch()) throw new RuntimeException('ไม่สามารถจัดการบัญชีนี้ได้');

        $pdo->prepare('UPDATE users SET account_status=? WHERE user_id=?')->execute([$status, $userId]);
        admin_notify_user($pdo, $userId, 'สถานะบัญชี FLEXJOB', $status === 'active' ? 'บัญชีของคุณเปิดใช้งานแล้ว' : 'บัญชีของคุณถูกระงับการใช้งาน');
        flash('success', $status === 'active' ? 'เปิดใช้งานบัญชีแล้ว' : 'ระงับบัญชีแล้ว');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('admin/users.php');
}

$query = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
if (!in_array($roleFilter, ['worker', 'employer'], true)) {
    $roleFilter = '';
}

$sql = "SELECT user_id, first_name, last_name, email, phone, role, account_status, created_at FROM users WHERE role IN ('worker', 'employer')";
$params = [];
if ($roleFilter !== '') {
    $sql .= ' AND role=?';
    $params[] = $roleFilter;
}
if ($query !== '') {
    $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
    $term = '%' . $query . '%';
    array_push($params, $term, $term, $term);
}
$sql .= ' ORDER BY created_at DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$accounts = $statement->fetchAll();

$pageTitle = 'จัดการบัญชีผู้ใช้ | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow">ACCOUNT MANAGEMENT</p>
            <h1 class="h2 mb-1">จัดการบัญชีผู้ใช้</h1>
            <p class="text-secondary mb-0">เปิดใช้งานหรือระงับบัญชี Worker และ Employer</p>
        </div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/dashboard.php">กลับหน้า Admin</a>
    </div>
    <form class="row g-2 mb-4" method="get">
        <div class="col-md-5"><input class="form-control" name="q" value="<?= e($query) ?>" placeholder="ค้นหาชื่อ นามสกุล หรืออีเมล"></div>
        <div class="col-md-3"><select class="form-select" name="role" aria-label="กรองประเภทบัญชี">
                <option value="">ผู้ใช้งานทั้งหมด</option>
                <option value="worker" <?= $roleFilter === 'worker' ? 'selected' : '' ?>>ผู้หางาน</option>
                <option value="employer" <?= $roleFilter === 'employer' ? 'selected' : '' ?>>ผู้ว่าจ้าง</option>
            </select></div>
        <div class="col-auto"><button class="btn btn-success" type="submit">ค้นหา</button></div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <?php foreach ($accounts as $account): ?><article class="border-bottom py-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <h2 class="h6 mb-1"><?= e($account['first_name'] . ' ' . $account['last_name']) ?></h2>
                            <p class="text-secondary small mb-0"><?= e($account['email']) ?> · <?= e($account['phone'] ?: '-') ?> · <?= $account['role'] === 'employer' ? 'ผู้ว่าจ้าง' : 'ผู้หางาน' ?></p>
                            <?php if ($account['role'] === 'employer'): ?><a class="btn btn-sm btn-outline-secondary mt-2" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $account['user_id'] ?>">ดูข้อมูลผู้ว่าจ้าง</a><?php endif; ?>
                        </div>
                        <form class="col-lg-4" method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $account['user_id'] ?>">
                            <div class="input-group"><select class="form-select" name="status">
                                    <option value="active" <?= $account['account_status'] === 'active' ? 'selected' : '' ?>>ใช้งาน</option>
                                    <option value="suspended" <?= $account['account_status'] === 'suspended' ? 'selected' : '' ?>>ระงับบัญชี</option>
                                </select><button class="btn btn-success" type="submit">บันทึก</button></div>
                        </form>
                    </div>
                </article><?php endforeach; ?>
            <?php if (!$accounts): ?><p class="text-secondary mb-0">ไม่พบผู้ใช้</p><?php endif; ?>
        </div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
