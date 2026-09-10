<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $note = trim((string) ($_POST['moderation_note'] ?? ''));
        if (!$reviewId || !in_array($action, ['hide','restore','dismiss'], true)) throw new RuntimeException('คำสั่งไม่ถูกต้อง');
        $pdo->beginTransaction();
        if ($action === 'hide') {
            $pdo->prepare("UPDATE reviews SET review_status='hidden',moderated_by_user_id=?,moderated_at=NOW(),moderation_note=? WHERE review_id=?")
                ->execute([(int) user()['id'],$note ?: null,$reviewId]);
            $pdo->prepare("UPDATE review_reports SET report_status='resolved',resolved_by_user_id=?,resolved_at=NOW() WHERE review_id=? AND report_status='pending'")
                ->execute([(int) user()['id'],$reviewId]);
            flash('success', 'ซ่อนรีวิวและปิดรายงานแล้ว');
        } elseif ($action === 'restore') {
            $pdo->prepare("UPDATE reviews SET review_status='visible',moderated_by_user_id=?,moderated_at=NOW(),moderation_note=? WHERE review_id=?")
                ->execute([(int) user()['id'],$note ?: null,$reviewId]);
            flash('success', 'แสดงรีวิวอีกครั้งแล้ว');
        } else {
            $pdo->prepare("UPDATE review_reports SET report_status='dismissed',resolved_by_user_id=?,resolved_at=NOW() WHERE review_id=? AND report_status='pending'")
                ->execute([(int) user()['id'],$reviewId]);
            flash('success', 'ยกเลิกรายงานแล้ว');
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $exception->getMessage());
    }
    redirect('admin/reviews.php');
}

$filter = in_array((string) ($_GET['status'] ?? ''), ['pending','hidden','all'], true) ? (string) $_GET['status'] : 'pending';
$where = $filter === 'pending' ? "EXISTS(SELECT 1 FROM review_reports rr2 WHERE rr2.review_id=r.review_id AND rr2.report_status='pending')" : ($filter === 'hidden' ? "r.review_status='hidden'" : '1=1');
$statement = $pdo->query("SELECT r.*,j.job_title,
        CONCAT(reviewer.first_name,' ',reviewer.last_name) reviewer_name,
        CONCAT(reviewee.first_name,' ',reviewee.last_name) reviewee_name,
        COUNT(CASE WHEN rr.report_status='pending' THEN 1 END) pending_reports,
        GROUP_CONCAT(CASE WHEN rr.report_status='pending' THEN rr.report_reason END SEPARATOR ' • ') report_reasons
    FROM reviews r
    JOIN users reviewer ON reviewer.user_id=r.reviewer_user_id
    JOIN users reviewee ON reviewee.user_id=r.reviewee_user_id
    JOIN applications a ON a.application_id=r.application_id
    JOIN jobs j ON j.job_id=a.job_id
    LEFT JOIN review_reports rr ON rr.review_id=r.review_id
    WHERE {$where}
    GROUP BY r.review_id
    ORDER BY pending_reports DESC,r.created_at DESC LIMIT 100");
$reviews = $statement->fetchAll();
$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM review_reports WHERE report_status='pending'")->fetchColumn();
$pageTitle = 'ดูแลรีวิว | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>
<main id="content" class="container py-4 py-lg-5" tabindex="-1">
    <header class="card border-0 shadow-sm mb-4"><div class="card-body p-4 p-lg-5"><p class="eyebrow mb-2">REVIEW MODERATION</p><h1 class="display-6 mb-2">ดูแลรีวิว</h1><p class="text-secondary mb-0">มีรายงานรอตรวจ <?= number_format($pendingCount) ?> รายการ</p></div></header>
    <nav class="d-flex gap-2 mb-4" aria-label="กรองรีวิว"><a class="btn <?= $filter==='pending'?'btn-primary':'btn-outline-primary' ?>" href="?status=pending">รอตรวจ</a><a class="btn <?= $filter==='hidden'?'btn-primary':'btn-outline-primary' ?>" href="?status=hidden">ซ่อนอยู่</a><a class="btn <?= $filter==='all'?'btn-primary':'btn-outline-primary' ?>" href="?status=all">ทั้งหมด</a></nav>
    <div class="vstack gap-3">
        <?php foreach ($reviews as $review): ?><article class="card border-0 shadow-sm"><div class="card-body p-4"><div class="d-flex flex-wrap justify-content-between gap-3"><div><span class="badge text-bg-<?= $review['review_status']==='visible'?'success':'secondary' ?>"><?= e($review['review_status']) ?></span><h2 class="h5 mt-2 mb-1"><?= e($review['reviewer_name']) ?> → <?= e($review['reviewee_name']) ?></h2><p class="text-secondary mb-2"><?= e($review['job_title']) ?> · <?= str_repeat('★',(int)$review['rating']) ?></p><p class="mb-2"><?= nl2br(e($review['review_comment'] ?: 'ไม่มีความคิดเห็นประกอบ')) ?></p><?php if($review['report_reasons']): ?><div class="alert alert-warning mb-0"><b>เหตุผลที่รายงาน:</b> <?= e($review['report_reasons']) ?></div><?php endif ?></div><form method="post" class="d-flex flex-column gap-2" style="min-width:260px"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= (int)$review['review_id'] ?>"><textarea class="form-control" name="moderation_note" rows="2" maxlength="1000" placeholder="หมายเหตุผู้ดูแล"></textarea><?php if($review['review_status']==='visible'): ?><button class="btn btn-danger" name="action" value="hide">ซ่อนรีวิว</button><?php else: ?><button class="btn btn-success" name="action" value="restore">แสดงรีวิว</button><?php endif ?><?php if((int)$review['pending_reports']>0): ?><button class="btn btn-outline-secondary" name="action" value="dismiss">ยกเลิกรายงาน</button><?php endif ?></form></div></div></article><?php endforeach ?>
        <?php if (!$reviews): ?><div class="card border-0 shadow-sm"><div class="card-body p-5 text-center text-secondary">ไม่มีรีวิวในรายการนี้</div></div><?php endif ?>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
