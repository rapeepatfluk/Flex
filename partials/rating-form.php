<?php
/** @var int $ratingApplicationId */
/** @var string $ratingTargetName */
/** @var string $ratingTargetRole */
/** @var array{average: float|int|string|null, count: int|string} $ratingSummary */
/** @var bool $ratingAlreadySubmitted */
$ratingAverage = (float) ($ratingSummary['average'] ?? 0);
$ratingCount = (int) ($ratingSummary['count'] ?? 0);
$ratingInputPrefix = 'rating-' . $ratingApplicationId;
?>
<div class="rating-summary mb-3">
    <span class="rating-summary-stars" aria-hidden="true">★</span>
    <?php if ($ratingCount): ?>
        <strong><?= number_format($ratingAverage, 1) ?></strong><span>/ 5 จาก <?= $ratingCount ?> รีวิว</span>
    <?php else: ?>
        <strong>ยังไม่มีคะแนน</strong><span>เป็นรีวิวที่ยืนยันจากงานที่เสร็จสิ้นแล้ว</span>
    <?php endif ?>
</div>

<?php if ($ratingAlreadySubmitted): ?>
    <div class="rating-complete">✓ คุณให้คะแนน<?= e($ratingTargetRole) ?>คนนี้แล้ว</div>
<?php else: ?>
    <form class="rating-form" method="post" action="<?= BASE_URL ?>/rate-application.php">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= $ratingApplicationId ?>">
        <fieldset>
            <legend class="h6 mb-1">ให้คะแนน<?= e($ratingTargetRole) ?></legend>
            <p class="text-secondary small mb-3">คุณให้คะแนน <?= e($ratingTargetName) ?> ได้ 1 ครั้งสำหรับงานนี้</p>
            <div class="rating-stars" aria-describedby="<?= $ratingInputPrefix ?>-hint">
                <?php for ($score = 5; $score >= 1; $score--): ?>
                    <input class="rating-input" id="<?= $ratingInputPrefix ?>-<?= $score ?>" type="radio" name="rating" value="<?= $score ?>" required>
                    <label class="rating-star" for="<?= $ratingInputPrefix ?>-<?= $score ?>"><span class="visually-hidden"><?= $score ?> ดาว</span><span aria-hidden="true">★</span></label>
                <?php endfor ?>
            </div>
            <p class="form-text mb-3" id="<?= $ratingInputPrefix ?>-hint">เลือกจำนวนดาวจาก 1 ถึง 5 ดาว</p>
            <button class="btn btn-primary" type="submit">บันทึกคะแนน</button>
        </fieldset>
    </form>
<?php endif ?>