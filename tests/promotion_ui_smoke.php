<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$employerPage = file_get_contents($root . '/employer/subscription.php');
$legacyPage = file_get_contents($root . '/employer/promote.php');
$adminPage = file_get_contents($root . '/admin/subscriptions.php');
$dashboardPage = file_get_contents($root . '/employer/dashboard.php');
$listingPage = file_get_contents($root . '/jobs.php');
$homePage = file_get_contents($root . '/index.php');

foreach (['id="promotionQr"','name="payment_slip"','name="action" value="upload_slip"','name="action" value="use_promotion"'] as $required) {
    if (!str_contains($employerPage, $required)) throw new RuntimeException('Employer subscription workflow is incomplete: ' . $required);
}
foreach (['value="approve"','value="reject"','type=subscription_slip'] as $required) {
    if (!str_contains($adminPage, $required)) throw new RuntimeException('Admin subscription review is incomplete: ' . $required);
}
if (!str_contains($legacyPage, "redirect('employer/subscription.php'") || str_contains($legacyPage, 'promotion_packages')) {
    throw new RuntimeException('Legacy standalone promotion page was not retired safely');
}
if (!str_contains($dashboardPage, '/employer/subscription.php')) throw new RuntimeException('Employer dashboard does not link to the subscription plan');

if (!str_contains($listingPage, 'promo.display_priority DESC') || !str_contains($listingPage, 'is-promoted')) {
    throw new RuntimeException('Promoted jobs are not prioritized and marked in the listing');
}
$promotedPosition = strpos($homePage, 'class="worker-promoted');
$recommendedPosition = strpos($homePage, 'class="worker-recommended');
$latestPosition = strpos($homePage, 'class="worker-latest');
if ($promotedPosition === false || $recommendedPosition === false || $latestPosition === false
    || !($promotedPosition < $recommendedPosition && $recommendedPosition < $latestPosition)) {
    throw new RuntimeException('Worker homepage promotion sections are missing or incorrectly ordered');
}

echo "subscription promotion UI smoke test: PASS\n";
