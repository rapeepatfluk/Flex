<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$pdo = db();
$workerId = (int) user()['id'];

$profileStatement = $pdo->prepare("SELECT u.username,u.first_name,u.last_name,u.email,u.phone,u.created_at,
        wp.professional_headline,wp.biography,wp.profile_image_path,wp.resume_file_path,
        wp.portfolio_file_path,wp.portfolio_url,wp.profile_visibility,wp.work_province,
        wp.preferred_work_mode,wp.available_from
    FROM users u LEFT JOIN worker_profiles wp ON wp.user_id=u.user_id
    WHERE u.user_id=? AND u.role='worker'");
$profileStatement->execute([$workerId]);
$profile = $profileStatement->fetch() ?: [];

$skillStatement = $pdo->prepare('SELECT s.skill_name FROM worker_skills ws JOIN skills s ON s.skill_id=ws.skill_id WHERE ws.worker_user_id=? ORDER BY s.skill_name');
$skillStatement->execute([$workerId]);
$skills = $skillStatement->fetchAll(PDO::FETCH_COLUMN);

$interestStatement = $pdo->prepare('SELECT wi.interest_name FROM worker_work_interests wwi JOIN work_interests wi ON wi.work_interest_id=wwi.work_interest_id WHERE wwi.worker_user_id=? ORDER BY wi.sort_order,wi.interest_name');
$interestStatement->execute([$workerId]);
$interests = $interestStatement->fetchAll(PDO::FETCH_COLUMN);

$applicationStatement = $pdo->prepare("SELECT a.application_id,a.application_status AS status,a.completed_at,a.created_at,
        j.job_title AS title,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,
        ep.company_name,ep.company_logo_path
    FROM applications a
    JOIN jobs j ON j.job_id=a.job_id
    JOIN employer_profiles ep ON ep.user_id=j.employer_user_id
    WHERE a.worker_user_id=? AND a.application_status<>'withdrawn'
    ORDER BY COALESCE(a.completed_at,a.created_at) DESC,a.application_id DESC");
$applicationStatement->execute([$workerId]);
$applications = $applicationStatement->fetchAll();

$statusLabels = [
    'submitted' => 'รอพิจารณา',
    'eligible' => 'มีสิทธิ์สัมภาษณ์',
    'interview_passed' => 'ผ่านสัมภาษณ์แล้ว',
    'completed' => 'งานเสร็จสิ้น',
    'not_selected' => 'ไม่ผ่าน',
];
$statusCounts = array_count_values(array_column($applications, 'status'));
$activeStatuses = ['submitted', 'eligible', 'interview_passed'];
$activeCount = array_sum(array_map(static fn(string $status): int => (int) ($statusCounts[$status] ?? 0), $activeStatuses));

$historyFilters = [
    'all' => ['label' => 'ทั้งหมด', 'statuses' => null],
    'active' => ['label' => 'กำลังดำเนินการ', 'statuses' => $activeStatuses],
    'completed' => ['label' => 'งานที่เสร็จสิ้น', 'statuses' => ['completed']],
    'not_selected' => ['label' => 'งานที่ไม่ผ่าน', 'statuses' => ['not_selected']],
];
$historyFilter = (string) ($_GET['history'] ?? 'all');
if (!isset($historyFilters[$historyFilter])) $historyFilter = 'all';
$historyStatuses = $historyFilters[$historyFilter]['statuses'];
$filteredApplications = $historyStatuses === null
    ? $applications
    : array_values(array_filter($applications, static fn(array $application): bool => in_array($application['status'], $historyStatuses, true)));

$invitationCountStatement = $pdo->prepare("SELECT COUNT(*) total_count,
        COALESCE(SUM(invitation_status IN ('sent','viewed')),0) pending_count,
        COALESCE(SUM(invitation_status='accepted'),0) accepted_count
    FROM job_invitations WHERE worker_user_id=?");
$invitationCountStatement->execute([$workerId]);
$invitationCounts = $invitationCountStatement->fetch() ?: ['total_count' => 0, 'pending_count' => 0, 'accepted_count' => 0];

$invitationStatement = $pdo->prepare("SELECT ji.job_invitation_id,ji.invitation_status,ji.created_at,
        j.job_id,j.job_title,j.work_location,j.pay_amount,j.pay_unit,
        ep.company_name,ep.company_logo_path
    FROM job_invitations ji
    JOIN jobs j ON j.job_id=ji.job_id
    JOIN employer_profiles ep ON ep.user_id=j.employer_user_id
    WHERE ji.worker_user_id=?
    ORDER BY ji.created_at DESC,ji.job_invitation_id DESC LIMIT 4");
$invitationStatement->execute([$workerId]);
$invitations = $invitationStatement->fetchAll();
$invitationStatusLabels = ['sent' => 'คำเชิญใหม่', 'viewed' => 'รอตอบรับ', 'accepted' => 'ตอบรับแล้ว', 'declined' => 'ปฏิเสธแล้ว'];

$ratingSummary = review_received_summary($pdo, $workerId);
$reviews = review_received_list($pdo, $workerId, 4);
$reportedReviewStatuses = [];
if ($reviews) {
    $reviewIds = array_map(static fn(array $review): int => (int) $review['review_id'], $reviews);
    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    $reportStatement = $pdo->prepare("SELECT review_id,report_status FROM review_reports WHERE reporter_user_id=? AND review_id IN ({$placeholders})");
    $reportStatement->execute(array_merge([$workerId], $reviewIds));
    foreach ($reportStatement->fetchAll() as $reportedReview) {
        $reportedReviewStatuses[(int) $reportedReview['review_id']] = $reportedReview['report_status'];
    }
}

$profileChecks = [
    trim(($profile['first_name'] ?? '') . ($profile['last_name'] ?? '')) !== '',
    trim((string) ($profile['phone'] ?? '')) !== '',
    trim((string) ($profile['professional_headline'] ?? '')) !== '',
    trim((string) ($profile['biography'] ?? '')) !== '',
    !empty($profile['profile_image_path']),
    !empty($profile['resume_file_path']),
    count($skills) > 0,
    count($interests) > 0,
];
$profileCompletion = (int) round(count(array_filter($profileChecks)) / count($profileChecks) * 100);
$displayName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$workModeLabels = ['any' => 'ทุกรูปแบบ', 'onsite' => 'ทำงานที่สถานที่', 'remote' => 'ทำงานทางไกล', 'hybrid' => 'ไฮบริด'];
$visibilityLabels = ['searchable' => 'ผู้ว่าจ้างค้นหาได้', 'application_only' => 'เห็นเมื่อสมัครงาน'];

$pageTitle = 'โปรไฟล์และประวัติของฉัน | FLEXJOB';
$pageStyles = ['worker-profile'];
require APP_ROOT . '/partials/header.php';
?>

<main class="worker-profile-page py-4 py-lg-5">
    <div class="container">
        <section class="profile-overview card border-0 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="profile-overview-grid">
                    <div class="profile-identity">
                        <div class="profile-avatar" aria-hidden="true">
                            <?php if (!empty($profile['profile_image_path'])): ?>
                                <img src="<?= BASE_URL . '/' . e($profile['profile_image_path']) ?>" alt="">
                            <?php else: ?>
                                <?= e(mb_substr($profile['first_name'] ?? 'F', 0, 1)) ?>
                            <?php endif ?>
                        </div>
                        <div class="min-w-0">
                            <p class="profile-kicker mb-1">MY PROFILE</p>
                            <h1 class="mb-1"><?= e($displayName ?: 'โปรไฟล์ของฉัน') ?></h1>
                            <p class="profile-headline mb-2"><?= e($profile['professional_headline'] ?: 'เพิ่มคำแนะนำตัวสั้น ๆ เพื่อให้ผู้ว่าจ้างรู้จักคุณ') ?></p>
                            <div class="profile-meta">
                                <span>⌖ <?= e($profile['work_province'] ?: FLEXJOB_PROVINCE) ?></span>
                                <span>◫ <?= e($workModeLabels[$profile['preferred_work_mode'] ?? 'any'] ?? 'ทุกรูปแบบ') ?></span>
                                <span>◉ <?= e($visibilityLabels[$profile['profile_visibility'] ?? 'application_only']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-completion">
                        <div class="profile-completion-copy"><span>ความครบถ้วนของโปรไฟล์</span><strong><?= $profileCompletion ?>%</strong></div>
                        <div class="profile-progress" role="progressbar" aria-label="ความครบถ้วนของโปรไฟล์" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $profileCompletion ?>"><span style="width: <?= $profileCompletion ?>%"></span></div>
                        <p class="mb-3"><?= $profileCompletion === 100 ? 'โปรไฟล์พร้อมสำหรับโอกาสงานใหม่แล้ว' : 'เติมข้อมูลให้ครบเพื่อเพิ่มโอกาสได้รับคำเชิญ' ?></p>
                        <a class="btn btn-light text-primary fw-semibold" href="<?= BASE_URL ?>/worker/editprofiles.php">แก้ไขข้อมูลและ Resume</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="profile-stats" aria-label="สรุปประวัติของฉัน">
            <a class="profile-stat <?= $historyFilter === 'all' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/worker/profile.php?history=all#application-history"><span class="profile-stat-icon blue">▤</span><span><small>งานที่สมัคร</small><strong><?= count($applications) ?></strong><em>รายการ</em></span></a>
            <a class="profile-stat <?= $historyFilter === 'active' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/worker/profile.php?history=active#application-history"><span class="profile-stat-icon amber">◷</span><span><small>กำลังดำเนินการ</small><strong><?= $activeCount ?></strong><em>รายการ</em></span></a>
            <a class="profile-stat <?= $historyFilter === 'completed' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/worker/profile.php?history=completed#application-history"><span class="profile-stat-icon green">✓</span><span><small>งานที่เสร็จสิ้น</small><strong><?= (int) ($statusCounts['completed'] ?? 0) ?></strong><em>รายการ</em></span></a>
            <a class="profile-stat <?= $historyFilter === 'not_selected' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/worker/profile.php?history=not_selected#application-history"><span class="profile-stat-icon red">×</span><span><small>งานที่ไม่ผ่าน</small><strong><?= (int) ($statusCounts['not_selected'] ?? 0) ?></strong><em>รายการ</em></span></a>
            <a class="profile-stat" href="<?= BASE_URL ?>/worker/invitations.php"><span class="profile-stat-icon violet">✦</span><span><small>คำเชิญ</small><strong><?= (int) $invitationCounts['total_count'] ?></strong><em><?= (int) $invitationCounts['pending_count'] ?> รายการรอตอบ</em></span></a>
        </section>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <section class="profile-panel" id="application-history" aria-labelledby="application-history-title">
                    <div class="profile-panel-header">
                        <div><p class="profile-section-kicker mb-1">APPLICATION HISTORY</p><h2 id="application-history-title">ประวัติการสมัครงาน</h2></div>
                        <a href="<?= BASE_URL ?>/jobs.php">ค้นหางานเพิ่ม <span aria-hidden="true">→</span></a>
                    </div>
                    <nav class="history-filters" aria-label="กรองประวัติใบสมัคร">
                        <?php foreach ($historyFilters as $filterKey => $filter): ?>
                            <a class="<?= $historyFilter === $filterKey ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/worker/profile.php?history=<?= e($filterKey) ?>#application-history"><?= e($filter['label']) ?></a>
                        <?php endforeach ?>
                    </nav>
                    <div class="profile-list">
                        <?php foreach ($filteredApplications as $application): ?>
                            <a class="history-item" href="<?= BASE_URL ?>/worker/application-detail.php?id=<?= (int) $application['application_id'] ?>">
                                <span class="company-mark">
                                    <?php if ($application['company_logo_path']): ?><img src="<?= BASE_URL . '/' . e($application['company_logo_path']) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><?= e(mb_substr($application['company_name'], 0, 1)) ?><?php endif ?>
                                </span>
                                <span class="history-main min-w-0"><strong><?= e($application['title']) ?></strong><small><?= e($application['company_name']) ?></small><span class="history-meta"><span>⌖ <?= e($application['location'] ?: 'ไม่ระบุสถานที่') ?></span><span><?= pay_text($application) ?></span></span></span>
                                <span class="history-side"><span class="status-pill <?= e($application['status']) ?>"><?= e($statusLabels[$application['status']] ?? $application['status']) ?></span><small><?= $application['status'] === 'completed' && $application['completed_at'] ? 'เสร็จเมื่อ ' . date('d/m/Y', strtotime($application['completed_at'])) : 'สมัครเมื่อ ' . date('d/m/Y', strtotime($application['created_at'])) ?></small><b>ดูรายละเอียด →</b></span>
                            </a>
                        <?php endforeach ?>
                        <?php if (!$filteredApplications): ?>
                            <div class="profile-empty"><span aria-hidden="true">⌕</span><h3>ยังไม่มี<?= e($historyFilters[$historyFilter]['label']) ?></h3><p><?= $historyFilter === 'all' ? 'เมื่อสมัครงานแล้ว ประวัติจะปรากฏที่นี่' : 'ยังไม่มีรายการในสถานะนี้' ?></p></div>
                        <?php endif ?>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <div class="profile-side-stack">
                    <section class="profile-panel profile-details" aria-labelledby="profile-details-title">
                        <div class="profile-panel-header compact"><div><p class="profile-section-kicker mb-1">ABOUT ME</p><h2 id="profile-details-title">ข้อมูลของฉัน</h2></div><a href="<?= BASE_URL ?>/worker/editprofiles.php">แก้ไข</a></div>
                        <p class="profile-biography"><?= $profile['biography'] ? nl2br(e($profile['biography'])) : 'ยังไม่ได้เพิ่มข้อความแนะนำตัว' ?></p>
                        <dl class="profile-detail-list">
                            <div><dt>อีเมล</dt><dd><?= e($profile['email'] ?? '-') ?></dd></div>
                            <div><dt>โทรศัพท์</dt><dd><?= e($profile['phone'] ?: 'ยังไม่ระบุ') ?></dd></div>
                            <div><dt>พร้อมเริ่มงาน</dt><dd><?= $profile['available_from'] ? date('d/m/Y', strtotime($profile['available_from'])) : 'ยังไม่ระบุ' ?></dd></div>
                            <div><dt>สมาชิกตั้งแต่</dt><dd><?= !empty($profile['created_at']) ? date('d/m/Y', strtotime($profile['created_at'])) : '-' ?></dd></div>
                        </dl>
                        <div class="profile-tag-group"><h3>ความสามารถ</h3><div><?php foreach ($skills as $skill): ?><span><?= e($skill) ?></span><?php endforeach ?><?php if (!$skills): ?><small>ยังไม่ได้เพิ่มความสามารถ</small><?php endif ?></div></div>
                        <div class="profile-tag-group"><h3>งานที่สนใจ</h3><div><?php foreach ($interests as $interest): ?><span><?= e($interest) ?></span><?php endforeach ?><?php if (!$interests): ?><small>ยังไม่ได้เลือกงานที่สนใจ</small><?php endif ?></div></div>
                    </section>

                    <section class="profile-panel invitation-preview" aria-labelledby="invitation-preview-title">
                        <div class="profile-panel-header compact"><div><p class="profile-section-kicker mb-1">INVITATIONS</p><h2 id="invitation-preview-title">คำเชิญล่าสุด</h2></div><a href="<?= BASE_URL ?>/worker/invitations.php">ดูทั้งหมด</a></div>
                        <div class="mini-list">
                            <?php foreach ($invitations as $invitation): ?>
                                <a href="<?= BASE_URL ?>/job.php?id=<?= (int) $invitation['job_id'] ?>"><span class="mini-list-main"><strong><?= e($invitation['job_title']) ?></strong><small><?= e($invitation['company_name']) ?> · <?= date('d/m/Y', strtotime($invitation['created_at'])) ?></small></span><span class="invitation-pill <?= e($invitation['invitation_status']) ?>"><?= e($invitationStatusLabels[$invitation['invitation_status']] ?? $invitation['invitation_status']) ?></span></a>
                            <?php endforeach ?>
                            <?php if (!$invitations): ?><div class="side-empty">ยังไม่มีคำเชิญสมัครงาน</div><?php endif ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <section class="profile-panel review-panel mt-4" aria-labelledby="reviews-title">
            <div class="profile-panel-header">
                <div><p class="profile-section-kicker mb-1">MY REVIEWS</p><h2 id="reviews-title">รีวิวที่ฉันได้รับ</h2></div>
                <div class="review-score" aria-label="คะแนนเฉลี่ย"><span>★</span><?php if ((int) ($ratingSummary['count'] ?? 0) > 0): ?><strong><?= number_format((float) $ratingSummary['average'], 1) ?></strong><small>/ 5 จาก <?= (int) $ratingSummary['count'] ?> รีวิว</small><?php else: ?><strong>–</strong><small>ยังไม่มีรีวิว</small><?php endif ?></div>
            </div>
            <div class="review-grid">
                <?php foreach ($reviews as $review): ?>
                    <?php $reportStatus = $reportedReviewStatuses[(int) $review['review_id']] ?? null; ?>
                    <article class="review-card">
                        <div class="review-card-top"><span class="review-stars" aria-label="<?= (int) $review['rating'] ?> ดาว"><?= str_repeat('★', (int) $review['rating']) ?><i><?= str_repeat('★', 5 - (int) $review['rating']) ?></i></span><time datetime="<?= e(date('Y-m-d', strtotime($review['created_at']))) ?>"><?= date('d/m/Y', strtotime($review['created_at'])) ?></time></div>
                        <p>“<?= e($review['review_comment']) ?>”</p>
                        <footer><strong><?= e($review['reviewer_name']) ?></strong><span>จากงาน <?= e($review['job_title']) ?></span></footer>
                        <div class="review-card-actions">
                            <?php if ($reportStatus): ?>
                                <span class="review-report-state <?= e($reportStatus) ?>"><?= $reportStatus === 'pending' ? 'ส่งรายงานแล้ว · รอผู้ดูแลตรวจสอบ' : 'ผู้ดูแลตรวจสอบรายงานแล้ว' ?></span>
                            <?php else: ?>
                                <details class="review-report">
                                    <summary>รายงานรีวิว</summary>
                                    <form method="post" action="<?= BASE_URL ?>/report-review.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="review_id" value="<?= (int) $review['review_id'] ?>">
                                        <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/worker/profile.php')) ?>">
                                        <label for="report-reason-<?= (int) $review['review_id'] ?>">เหตุผลที่ต้องการรายงาน</label>
                                        <textarea id="report-reason-<?= (int) $review['review_id'] ?>" class="form-control form-control-sm" name="report_reason" rows="3" maxlength="500" required placeholder="เช่น ใช้ถ้อยคำไม่เหมาะสม หรือข้อมูลไม่เป็นความจริง"></textarea>
                                        <div><button class="btn btn-sm btn-outline-danger" type="submit">ส่งรายงานให้ผู้ดูแล</button></div>
                                    </form>
                                </details>
                            <?php endif ?>
                        </div>
                    </article>
                <?php endforeach ?>
                <?php if (!$reviews): ?><div class="profile-empty review-empty"><span aria-hidden="true">☆</span><h3>ยังไม่มีรีวิว</h3><p>รีวิวจากผู้ว่าจ้างจะแสดงเมื่องานเสร็จสิ้นและมีการให้คะแนน</p></div><?php endif ?>
            </div>
        </section>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
