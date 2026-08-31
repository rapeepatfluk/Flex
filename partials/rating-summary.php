<?php
/** @var array{average: float|int|string|null, count: int|string} $ratingSummary */
$ratingAverage = (float) ($ratingSummary['average'] ?? 0);
$ratingCount = (int) ($ratingSummary['count'] ?? 0);
?>
<div class="rating-summary" aria-label="คะแนนเฉลี่ย">
    <span class="rating-summary-stars" aria-hidden="true">★</span>
    <?php if ($ratingCount): ?>
        <strong><?= number_format($ratingAverage, 1) ?></strong>
        <span>/ 5 จาก <?= $ratingCount ?> รีวิว</span>
    <?php else: ?>
        <strong>ยังไม่มีคะแนน</strong>

    <?php endif ?>
</div>