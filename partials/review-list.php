<?php
/** @var array $reviews */
$reviewTargetLabel = $reviewTargetLabel ?? 'ผู้ใช้งาน';
?>
<div class="review-list">
    <?php if (!$reviews): ?>
        <p class="text-secondary mb-0">ยังไม่มีความคิดเห็นเกี่ยวกับ<?= e($reviewTargetLabel) ?></p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <article class="review-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div><strong><?= e($review['reviewer_name']) ?></strong><span class="review-stars" aria-label="<?= (int) $review['rating'] ?> ดาว"><?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?></span></div>
                    <time datetime="<?= e(date('c', strtotime($review['created_at']))) ?>"><?= date('d/m/Y', strtotime($review['created_at'])) ?></time>
                </div>
                <small class="text-secondary d-block mt-1">งาน: <?= e($review['job_title']) ?></small>
                <?php if (trim((string) ($review['review_comment'] ?? '')) !== ''): ?><p class="mb-0 mt-3"><?= nl2br(e($review['review_comment'])) ?></p><?php else: ?><p class="mb-0"><small class="text-secondary">รีวิวเดิมนี้ไม่มีความคิดเห็นประกอบ</small></p><?php endif; ?>
                <?php if (user() && (int) user()['id'] !== (int) $review['reviewer_user_id']): ?>
                    <details class="review-report mt-2"><summary>รายงานรีวิว</summary><form method="post" action="<?= BASE_URL ?>/report-review.php" class="d-flex flex-column gap-2 mt-2"><?= csrf_field() ?><input type="hidden" name="review_id" value="<?= (int) $review['review_id'] ?>"><input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php')) ?>"><textarea class="form-control form-control-sm" name="report_reason" rows="2" maxlength="500" required placeholder="ระบุเหตุผลที่รายงาน"></textarea><button class="btn btn-sm btn-outline-danger align-self-start" type="submit">ส่งรายงาน</button></form></details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
