<?php
$icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷');
?>
<div class="col-12 col-md-6 col-xl-4">
    <a class="card h-100 border-0 worker-job-card<?= !empty($job['is_promoted']) ? ' is-promoted' : '' ?>" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>" aria-label="ดูรายละเอียดงาน <?= e($job['title']) ?>">
        <?php if ($job['cover_image']): ?>
            <img class="card-img-top worker-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>" width="800" height="450" loading="lazy" decoding="async">
        <?php else: ?>
            <div class="worker-job-image worker-job-fallback" aria-hidden="true"><?= $icon ?></div>
        <?php endif ?>
        <div class="card-body worker-job-body d-flex flex-column">
            <?php if (!empty($job['is_promoted'])): ?><span class="badge worker-promoted-badge align-self-start mb-2">✦ <?= $job['promotion_code'] === 'featured-7d' ? 'ประกาศแนะนำ' : 'โปรโมต' ?></span><?php endif; ?>
            <h3><?= e($job['title']) ?></h3>
            <p><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>" width="40" height="40" loading="lazy" decoding="async"><?php endif; ?><?= e($job['company_name']) ?><?= $job['is_verified'] ? ' · ✓ ยืนยันแล้ว' : '' ?></p>
            <div class="worker-employer-rating"><?php $ratingSummary = ['average' => $job['employer_rating_average'], 'count' => $job['employer_rating_count']]; require APP_ROOT . '/partials/rating-summary.php'; ?></div>
            <p class="worker-job-meta">⌖ <?= e($job['location']) ?><br>◷ <?= e($job['work_date']) ?></p>
            <?php if (($job['match']['score'] ?? null) !== null): ?><div class="match-summary"><span class="badge text-bg-success"><?= $job['match']['score'] ?>% Match</span><small><?= e($job['match']['reasons'][0] ?? 'ตรงกับข้อมูลที่ระบุ') ?></small></div><?php endif; ?>
            <div class="worker-job-bottom mt-auto"><strong><?= pay_text($job) ?></strong><span><?= e($job['work_interest_name'] ?: job_type($job['job_type'])) ?></span></div>
        </div>
    </a>
</div>
