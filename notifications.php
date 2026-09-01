<?php
require_once __DIR__ . '/config/config.php';
require_login();

$pdo = db();
$userId = (int) user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if (($_POST['action'] ?? '') !== 'mark_all_read') throw new RuntimeException('คำขอไม่ถูกต้อง');
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0')->execute([$userId]);
        flash('success', 'ทำเครื่องหมายว่าอ่านทั้งหมดแล้ว');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    }
    redirect('notifications.php');
}

$notifications = notification_latest($pdo, $userId, 50);
$unread = notification_unread_count($pdo, $userId);
$pageTitle = 'การแจ้งเตือน | FLEXJOB';
$pageStyles = ['notifications'];
require APP_ROOT . '/partials/header.php';
?>
<main class="notifications-page py-4 py-lg-5"><div class="container">
    <header class="notifications-hero mb-4"><div><p class="eyebrow mb-1">NOTIFICATIONS</p><h1 class="h2 mb-1">การแจ้งเตือนทั้งหมด</h1><p class="text-secondary mb-0">ติดตามความเคลื่อนไหวของใบสมัคร คำเชิญ และบัญชีของคุณ</p></div><span class="notifications-unread"><?= number_format($unread) ?> รายการที่ยังไม่อ่าน</span></header>
    <div class="notifications-actions mb-3">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark_all_read"><button class="btn btn-outline-primary" type="submit" <?= $unread ? '' : 'disabled' ?>>อ่านทั้งหมด</button></form>
        <?php if ($notifications): ?><form method="post" action="<?= BASE_URL ?>/notification-clear.php" onsubmit="return confirm('ยืนยันลบประวัติการแจ้งเตือนทั้งหมด?')"><?= csrf_field() ?><input type="hidden" name="return_path" value="notifications.php"><button class="btn btn-outline-danger" type="submit">ล้างประวัติทั้งหมด</button></form><?php endif; ?>
    </div>
    <section class="notifications-list card border-0 shadow-sm">
        <?php foreach ($notifications as $notification): ?><a class="notifications-row <?= $notification['is_read'] ? 'is-read' : '' ?>" href="<?= BASE_URL ?>/notification.php?id=<?= $notification['notification_id'] ?>"><span class="notifications-dot" aria-hidden="true"></span><span class="flex-grow-1"><b><?= e($notification['notification_title']) ?></b><span><?= e($notification['notification_message']) ?></span><small><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></small></span></a><?php endforeach; ?>
        <?php if (!$notifications): ?><div class="notifications-empty"><span aria-hidden="true">♢</span><h2 class="h5">ยังไม่มีการแจ้งเตือน</h2><p class="text-secondary mb-0">เมื่อมีความเคลื่อนไหวใหม่ รายการจะแสดงที่นี่</p></div><?php endif; ?>
    </section>
</div></main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
