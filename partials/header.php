<?php
require_once __DIR__ . '/../config/config.php';

$currentUser = user();
$role = $currentUser['role'] ?? '';
$roleLabels = [
    'worker' => 'ผู้หางาน',
    'employer' => 'ผู้ว่าจ้าง',
    'admin' => 'ผู้ดูแลระบบ',
];
$roleLabel = $roleLabels[$role] ?? '';
$accountDisplayName = $currentUser
    ? preg_split('/\s+/u', trim($currentUser['name']))[0]
    : '';
$notifications = [];
$unreadNotifications = 0;
$accountAvatarPath = null;

if ($currentUser) {
    $notifications = notification_latest(db(), (int) $currentUser['id']);
    $unreadNotifications = notification_unread_count(db(), (int) $currentUser['id']);

    if ($role === 'worker') {
        $avatarStmt = db()->prepare('SELECT profile_image_path FROM worker_profiles WHERE user_id=?');
        $avatarStmt->execute([$currentUser['id']]);
        $accountAvatarPath = $avatarStmt->fetchColumn() ?: null;
    } elseif ($role === 'employer') {
        $avatarStmt = db()->prepare('SELECT company_logo_path FROM employer_profiles WHERE user_id=?');
        $avatarStmt->execute([$currentUser['id']]);
        $accountAvatarPath = $avatarStmt->fetchColumn() ?: null;
    }
}

$accountOverviewPath = $role === 'worker'
    ? 'worker/dashboard.php'
    : dashboard_path($role);
$brandPath = $role === 'admin' ? 'admin/dashboard.php' : 'index.php';

$accountLinks = match ($role) {
    'worker' => [
        ['icon' => '◎', 'label' => 'แบบสำรวจ Matching', 'path' => 'worker/matching-survey.php'],
        ['icon' => '▣', 'label' => 'ข้อมูลส่วนตัวและ Resume', 'path' => 'worker/editprofiles.php'],
        ['icon' => '◷', 'label' => 'งานที่สมัครของฉัน', 'path' => 'worker/dashboard.php#applications'],
        ['icon' => '✦', 'label' => 'คำเชิญสมัครงาน', 'path' => 'worker/invitations.php'],
    ],
    'employer' => [
        ['icon' => '▣', 'label' => 'ข้อมูลส่วนตัวและบริษัท', 'path' => 'employer/editprofile.php'],
        ['icon' => '＋', 'label' => 'สร้างประกาศงาน', 'path' => 'employer/jobpost.php'],
        ['icon' => '▣', 'label' => 'จัดการประกาศงาน', 'path' => 'employer/dashboard.php'],
        ['icon' => '✦', 'label' => 'โปรโมตประกาศ', 'path' => 'employer/dashboard.php#all-jobs'],
        ['icon' => '◎', 'label' => 'ค้นหาผู้หางาน', 'path' => 'employer/candidates.php'],
    ],
    'admin' => [
        ['icon' => '⌂', 'label' => 'ภาพรวมระบบ', 'path' => 'admin/dashboard.php'],
        ['icon' => '▤', 'label' => 'ตรวจเอกสาร', 'path' => 'admin/documents.php'],
        ['icon' => '◷', 'label' => 'จัดการประกาศ', 'path' => 'admin/jobs.php'],
        ['icon' => '฿', 'label' => 'ตรวจสลิปโปรโมต', 'path' => 'admin/promotions.php'],
        ['icon' => '◎', 'label' => 'จัดการบัญชี', 'path' => 'admin/users.php'],
    ],
    default => [],
};

$styles = array_merge(['header', 'header-theme'], $role === 'admin' ? ['admin-shell'] : [], $pageStyles ?? [], ['theme']);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($pageTitle ?? 'FLEXJOB') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/app.css?v=<?= filemtime(APP_ROOT . '/app.css') ?>">

    <?php foreach ($styles as $style):
        $styleFile = APP_ROOT . '/assets/css/' . $style . '.css';
        $styleVersion = is_file($styleFile) ? filemtime($styleFile) : time();
    ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= e($style) ?>.css?v=<?= $styleVersion ?>">
    <?php endforeach ?>
</head>

<body class="<?= $role === 'admin' ? 'admin-mode' : '' ?>">
    <header class="site-header">
        <a class="brand" href="<?= BASE_URL ?>/<?= $brandPath ?>">
            <span class="brand-mark">F</span>FLEX<span>JOB</span>
        </a>

        <nav class="main-nav">
            <?php if ($role === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php">ภาพรวมระบบ</a>
                <a href="<?= BASE_URL ?>/admin/documents.php">เอกสารผู้ว่าจ้าง</a>
                <a href="<?= BASE_URL ?>/admin/jobs.php">ตรวจสอบประกาศ</a>
                <a href="<?= BASE_URL ?>/admin/promotions.php">ตรวจสลิปโปรโมต</a>
                <a href="<?= BASE_URL ?>/admin/users.php">จัดการบัญชี</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/jobs.php">ค้นหางาน</a>
                <?php if ($role === 'employer'): ?><a href="<?= BASE_URL ?>/employer/candidates.php">ค้นหาผู้หางาน</a><?php endif ?>
                <?php if (!$currentUser || $role !== 'worker'): ?>
                    <a href="<?= BASE_URL ?>/employer/dashboard.php">สำหรับผู้ว่าจ้าง</a>
                <?php endif ?>
            <?php endif ?>
        </nav>

        <div class="header-actions">
            <?php if ($currentUser): ?>
                <details class="notification-menu" id="notificationMenu" data-feed-url="<?= BASE_URL ?>/notifications-feed.php">
                    <summary aria-label="การแจ้งเตือน">
                        <span class="notification-bell">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
                            <span class="notification-count" id="notificationCount" <?= $unreadNotifications ? '' : 'hidden' ?>><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
                        </span>
                    </summary>
                    <div class="notification-panel">
                        <strong>การแจ้งเตือน</strong>
                        <div id="notificationList"><?php foreach ($notifications as $notification): ?>
                            <a class="notification-item <?= $notification['is_read'] ? 'is-read' : '' ?>" href="<?= BASE_URL ?>/notification.php?id=<?= $notification['notification_id'] ?>">
                                <b><?= e($notification['notification_title']) ?></b>
                                <span><?= e($notification['notification_message']) ?></span>
                                <small><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></small>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$notifications): ?><p class="notification-empty">ยังไม่มีการแจ้งเตือน</p><?php endif; ?>
                        </div>
                        <div class="notification-panel-actions"><a href="<?= BASE_URL ?>/notifications.php">ดูการแจ้งเตือนทั้งหมด</a></div>
                    </div>
                </details>
                <details class="account-menu">
                    <summary>
                        <span class="account-avatar" aria-label="บัญชีผู้ใช้">
                            <?php if ($accountAvatarPath): ?>
                            <img src="<?= BASE_URL . '/' . e($accountAvatarPath) ?>" alt="">
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="8" r="3.5"></circle>
                                <path d="M4.5 20c.8-4 3.3-6 7.5-6s6.7 2 7.5 6"></path>
                            </svg>
                            <?php endif ?>
                        </span>
                        <span>
                            <span class="account-name"><?= e($accountDisplayName) ?></span>
                            <span class="account-role"><?= e($roleLabel) ?></span>
                        </span>
                        <span class="account-caret">⌄</span>
                    </summary>

                    <div class="account-panel">
                        <strong>บัญชีของคุณ</strong>
                        <?php foreach ($accountLinks as $link): ?>
                            <a href="<?= BASE_URL ?>/<?= e($link['path']) ?>">
                                <span class="account-icon"><?= e($link['icon']) ?></span>
                                <?= e($link['label']) ?>
                            </a>
                        <?php endforeach ?>
                        <div class="account-divider"></div>
                        <a class="logout-link" href="<?= BASE_URL ?>/auth/logout.php">
                            <span class="account-icon">↪</span>ออกจากระบบ
                        </a>
                    </div>
                </details>
            <?php else: ?>
                <a class="text-link" href="<?= BASE_URL ?>/auth/login.php">เข้าสู่ระบบ</a>
                <a class="text-link signup-link" href="<?= BASE_URL ?>/auth/register.php">สมัครใช้งาน</a>
            <?php endif ?>
        </div>
    </header>
<?php if ($role === 'admin'):
    $currentAdminPage = basename((string) (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: ''));
    $currentAdminNavPage = match ($currentAdminPage) {
        'jobdelete.php' => 'jobs.php',
        'employer.php' => 'users.php',
        'promotions.php' => 'promotions.php',
        default => $currentAdminPage,
    };
?>
    <aside class="admin-sidebar" aria-label="เมนูผู้ดูแลระบบ">
        <p class="admin-sidebar-label">ADMIN MENU</p>
        <nav>
            <a class="<?= $currentAdminNavPage === 'dashboard.php' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php"><span aria-hidden="true">⌂</span>ภาพรวมระบบ</a>
            <a class="<?= $currentAdminNavPage === 'documents.php' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/documents.php"><span aria-hidden="true">▤</span>ตรวจเอกสาร</a>
            <a class="<?= $currentAdminNavPage === 'jobs.php' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/jobs.php"><span aria-hidden="true">◷</span>จัดการประกาศ</a>
            <a class="<?= $currentAdminNavPage === 'promotions.php' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/promotions.php"><span aria-hidden="true">฿</span>ตรวจสลิปโปรโมต</a>
            <a class="<?= $currentAdminNavPage === 'users.php' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php"><span aria-hidden="true">◎</span>จัดการบัญชี</a>
        </nav>
    </aside>
<?php endif; ?>

    <?php if ($message = flash('success')): ?>
        <div class="flash alert alert-success" role="alert"><?= e($message) ?></div>
    <?php endif ?>
    <?php if ($message = flash('error')): ?>
        <div class="flash alert alert-danger" role="alert"><?= e($message) ?></div>
    <?php endif ?>
