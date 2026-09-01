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

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET account_status=? WHERE user_id=?')->execute([$status, $userId]);
        admin_notify_user($pdo, $userId, 'สถานะบัญชี FLEXJOB', $status === 'active' ? 'บัญชีของคุณเปิดใช้งานแล้ว' : 'บัญชีของคุณถูกระงับการใช้งาน');
        $pdo->commit();
        flash('success', $status === 'active' ? 'เปิดใช้งานบัญชีแล้ว' : 'ระงับบัญชีแล้ว');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
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

$summary = ['worker' => 0, 'employer' => 0, 'active' => 0, 'suspended' => 0];
foreach ($accounts as $account) {
    if (isset($summary[$account['role']])) $summary[$account['role']]++;
    if (isset($summary[$account['account_status']])) $summary[$account['account_status']]++;
}

$pageTitle = 'จัดการบัญชีผู้ใช้ | FLEXJOB';
$pageStyles = ['admin-users'];
require APP_ROOT . '/partials/header.php';
?>

<main id="content" class="admin-users" tabindex="-1">
    <div class="container">
        <header class="admin-users-hero card border-0 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg">
                        <p class="admin-users-eyebrow mb-2">ACCOUNT MANAGEMENT</p>
                        <h1 class="display-6 mb-2">จัดการบัญชีผู้ใช้</h1>
                        <p class="admin-users-lead mb-0">ค้นหา ตรวจสอบ และเปลี่ยนสถานะบัญชีผู้หางานหรือผู้ว่าจ้างได้จากหน้าเดียว</p>
                    </div>
                    <div class="col-lg-auto"><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/dashboard.php">กลับหน้า Admin</a></div>
                </div>
            </div>
        </header>

        <section class="row g-3 mb-4" aria-label="สรุปบัญชีผู้ใช้">
            <?php foreach ([
                ['worker', 'ผู้หางาน', '◎', 'blue'],
                ['employer', 'ผู้ว่าจ้าง', '▣', 'sky'],
                ['active', 'บัญชีใช้งานอยู่', '✓', 'green'],
                ['suspended', 'บัญชีถูกระงับ', '!', 'red'],
            ] as [$key, $label, $icon, $tone]): ?>
                <div class="col-6 col-xl-3">
                    <article class="card border-0 admin-users-stat admin-users-stat-<?= e($tone) ?> h-100">
                        <div class="card-body p-3 p-lg-4">
                            <span class="admin-users-stat-icon" aria-hidden="true"><?= e($icon) ?></span>
                            <p class="mb-1"><?= e($label) ?></p>
                            <strong><?= number_format($summary[$key]) ?></strong>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="card border-0 admin-users-filter mb-4" aria-labelledby="user-filter-heading">
            <div class="card-body p-3 p-lg-4">
                <h2 class="visually-hidden" id="user-filter-heading">ค้นหาและกรองบัญชีผู้ใช้</h2>
                <form class="row g-2 align-items-end" method="get" action="<?= BASE_URL ?>/admin/users.php">
                    <div class="col-lg">
                        <label class="form-label" for="account-search">ค้นหาผู้ใช้</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">⌕</span>
                            <input class="form-control" id="account-search" name="q" value="<?= e($query) ?>" placeholder="ชื่อ นามสกุล หรืออีเมล">
                        </div>
                    </div>
                    <div class="col-sm-5 col-lg-3">
                        <label class="form-label" for="account-role">ประเภทบัญชี</label>
                        <select class="form-select" id="account-role" name="role">
                            <option value="">ผู้ใช้งานทั้งหมด</option>
                            <option value="worker" <?= $roleFilter === 'worker' ? 'selected' : '' ?>>ผู้หางาน</option>
                            <option value="employer" <?= $roleFilter === 'employer' ? 'selected' : '' ?>>ผู้ว่าจ้าง</option>
                        </select>
                    </div>
                    <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit">ค้นหา</button></div>
                    <?php if ($query !== '' || $roleFilter !== ''): ?><div class="col-sm-auto"><a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/admin/users.php">ล้างตัวกรอง</a></div><?php endif; ?>
                </form>
            </div>
        </section>

        <section aria-labelledby="account-list-heading">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-2 mb-3">
                <div><p class="admin-users-eyebrow mb-1">USER DIRECTORY</p><h2 class="h3 mb-0" id="account-list-heading"><?= $query !== '' || $roleFilter !== '' ? 'ผลการค้นหาบัญชี' : 'บัญชีผู้ใช้ทั้งหมด' ?></h2></div>
                <p class="small text-secondary mb-0">แสดง <?= number_format(count($accounts)) ?> บัญชี</p>
            </div>

            <?php if ($accounts): ?>
                <div class="row g-3">
                    <?php foreach ($accounts as $account):
                        $isEmployer = $account['role'] === 'employer';
                        $isActive = $account['account_status'] === 'active';
                        $statusId = 'account-status-' . $account['user_id'];
                    ?>
                        <div class="col-12">
                            <article class="card border-0 admin-user-card">
                                <div class="card-body p-4">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-xl">
                                            <div class="d-flex align-items-start gap-3">
                                                <span class="admin-user-avatar admin-user-avatar-<?= $isEmployer ? 'employer' : 'worker' ?>" aria-hidden="true"><?= e(mb_substr($account['first_name'], 0, 1)) ?></span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                        <span class="badge rounded-pill <?= $isEmployer ? 'text-bg-primary' : 'text-bg-info' ?>"><?= $isEmployer ? 'ผู้ว่าจ้าง' : 'ผู้หางาน' ?></span>
                                                        <span class="admin-user-status <?= $isActive ? 'is-active' : 'is-suspended' ?>"><span aria-hidden="true"><?= $isActive ? '✓' : '!' ?></span><?= $isActive ? 'ใช้งานอยู่' : 'ถูกระงับ' ?></span>
                                                    </div>
                                                    <h3 class="h5 mb-1"><?= e($account['first_name'] . ' ' . $account['last_name']) ?></h3>
                                                    <dl class="admin-user-meta mb-0">
                                                        <div><dt>อีเมล</dt><dd><?= e($account['email']) ?></dd></div>
                                                        <div><dt>เบอร์โทรศัพท์</dt><dd><?= e($account['phone'] ?: '-') ?></dd></div>
                                                        <div><dt>สมัครเมื่อ</dt><dd><?= date('d/m/Y', strtotime($account['created_at'])) ?></dd></div>
                                                    </dl>
                                                    <?php if ($isEmployer): ?><a class="admin-user-company-link" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $account['user_id'] ?>">ดูข้อมูลผู้ว่าจ้าง <span aria-hidden="true">→</span></a><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-auto">
                                            <form class="admin-user-status-form" method="post" action="<?= BASE_URL ?>/admin/users.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= $account['user_id'] ?>">
                                                <label for="<?= $statusId ?>">สถานะบัญชี</label>
                                                <div class="input-group">
                                                    <select class="form-select" id="<?= $statusId ?>" name="status">
                                                        <option value="active" <?= $isActive ? 'selected' : '' ?>>ใช้งาน</option>
                                                        <option value="suspended" <?= !$isActive ? 'selected' : '' ?>>ระงับบัญชี</option>
                                                    </select>
                                                    <button class="btn btn-primary" type="submit">บันทึก</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 admin-users-empty">
                    <div class="card-body text-center p-5">
                        <span class="admin-users-empty-icon" aria-hidden="true">⌕</span>
                        <h3 class="h4 mt-3">ไม่พบบัญชีผู้ใช้</h3>
                        <p class="text-secondary mb-4">ลองเปลี่ยนคำค้นหา หรือปรับประเภทบัญชีที่ต้องการดู</p>
                        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/users.php">ดูบัญชีทั้งหมด</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
