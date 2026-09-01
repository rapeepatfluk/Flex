<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$employerPage = file_get_contents($root . '/employer/promote.php');
$adminPage = file_get_contents($root . '/admin/promotions.php');
$listingPage = file_get_contents($root . '/jobs.php');
$homePage = file_get_contents($root . '/index.php');

foreach ([
    'promotion_promptpay_payload',
    'id="promotionQr"',
    'name="payment_slip"',
    'name="action" value="upload_slip"',
] as $required) {
    if (!str_contains($employerPage, $required)) {
        throw new RuntimeException('Employer promotion workflow markup is incomplete: ' . $required);
    }
}

foreach (['value="approve"', 'value="reject"', 'type=promotion_slip'] as $required) {
    if (!str_contains($adminPage, $required)) {
        throw new RuntimeException('Admin promotion review markup is incomplete: ' . $required);
    }
}

if (!str_contains($listingPage, "promo.display_priority DESC") || !str_contains($listingPage, 'is-promoted')) {
    throw new RuntimeException('Promoted jobs are not prioritized and marked in the listing');
}
$promotedPosition = strpos($homePage, 'class="worker-promoted');
$recommendedPosition = strpos($homePage, 'class="worker-recommended');
$latestPosition = strpos($homePage, 'class="worker-latest');
if ($promotedPosition === false || $recommendedPosition === false || $latestPosition === false
    || !($promotedPosition < $recommendedPosition && $recommendedPosition < $latestPosition)) {
    throw new RuntimeException('Worker homepage promotion sections are missing or incorrectly ordered');
}
foreach (['$promotedJobs', '$latestWorkerJobs', "empty(\$job['is_promoted'])", '/partials/worker-job-card.php'] as $required) {
    if (!str_contains($homePage, $required)) {
        throw new RuntimeException('Promoted jobs are not separated from recommendations and latest jobs: ' . $required);
    }
}
foreach (['งานโปรโมต', 'งานแนะนำสำหรับคุณ', 'งานที่เพิ่มล่าสุด'] as $heading) {
    if (!str_contains($homePage, $heading)) throw new RuntimeException('Worker homepage heading is missing: ' . $heading);
}
if (substr_count($homePage, '/partials/worker-job-card.php') !== 3) {
    throw new RuntimeException('Worker job sections do not share the same card template');
}

echo "promotion UI smoke test: PASS\n";
