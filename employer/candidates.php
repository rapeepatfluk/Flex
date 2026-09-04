<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$employerId = (int) user()['id'];
$verified = matching_employer_is_verified($pdo, $employerId);

$jobStatement = $pdo->prepare("SELECT j.job_id,j.job_title,j.work_location,j.work_schedule,j.pay_amount,j.pay_unit,j.open_positions,j.created_at,wi.interest_name work_interest_name FROM jobs j LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id WHERE j.employer_user_id=? AND j.work_province=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.created_at DESC");
$jobStatement->execute([$employerId, FLEXJOB_PROVINCE]);
$employerJobs = $jobStatement->fetchAll();
$latestJobs = array_slice($employerJobs, 0, 3);

$selectedJobId = (int) ($_GET['job'] ?? $_POST['job_id'] ?? ($employerJobs[0]['job_id'] ?? 0));
$ownedJobIds = array_map('intval', array_column($employerJobs, 'job_id'));
if ($selectedJobId && !in_array($selectedJobId, $ownedJobIds, true)) $selectedJobId = 0;

$selectedJob = null;
foreach ($employerJobs as $job) {
    if ((int) $job['job_id'] === $selectedJobId) {
        $selectedJob = $job;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        matching_send_invitation($pdo, $employerId, $selectedJobId, (int) ($_POST['worker_id'] ?? 0), trim($_POST['message'] ?? ''));
        flash('success', 'ส่งคำเชิญให้สมัครงานแล้ว');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('employer/candidates.php?job=' . $selectedJobId);
}

$workers = $verified && $selectedJobId ? matching_workers_for_job($pdo, $selectedJobId, $employerId) : [];
$query = mb_strtolower(trim($_GET['q'] ?? ''), 'UTF-8');
$minimumScore = max(0, min(100, (int) ($_GET['min_score'] ?? 0)));
$workers = array_values(array_filter($workers, function (array $worker) use ($query, $minimumScore): bool {
    $searchable = mb_strtolower(implode(' ', [$worker['name'], $worker['headline'], $worker['skill_names'], $worker['work_interest_names']]), 'UTF-8');
    if ($query !== '' && !str_contains($searchable, $query)) return false;
    return $minimumScore === 0 || (($worker['match']['score'] ?? -1) >= $minimumScore);
}));

$totalWorkers = count($workers);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$totalPages = max(1, (int) ceil($totalWorkers / $perPage));
if ($page > $totalPages) $page = $totalPages;
$workers = array_slice($workers, ($page - 1) * $perPage, $perPage);

$pageTitle = 'ค้นหาผู้หางาน | FLEXJOB';
$pageStyles = ['matching', 'employer-candidates'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container candidate-search-page py-4 py-lg-5">
    <section class="candidate-search-hero card border-0 shadow-sm mb-4" aria-labelledby="candidate-search-title">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-4">
            <div>
                <p class="eyebrow mb-2">TALENT MATCHING</p>
                <h1 id="candidate-search-title">ค้นหาผู้หางานที่เหมาะกับงาน</h1>
                <p class="text-secondary mb-0">เปรียบเทียบความสามารถ ความสนใจ และรูปแบบงาน เพื่อช่วยตัดสินใจได้เร็วขึ้น</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="candidate-job-count"><b><?= count($employerJobs) ?></b> ประกาศที่เปิดรับ</span>
                <span class="candidate-match-count"><b><?= $totalWorkers ?></b> โปรไฟล์ที่ตรง</span>
                <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/employer/dashboard.php">จัดการประกาศ</a>
            </div>
        </div>
    </section>

    <?php if (!$verified): ?>
        <div class="alert alert-warning shadow-sm border-0">บัญชีของคุณต้องผ่านการยืนยันก่อนค้นหาโปรไฟล์ผู้หางาน</div>
    <?php elseif (!$employerJobs): ?>
        <div class="alert alert-info shadow-sm border-0">คุณต้องมีประกาศงานที่เปิดรับสมัครก่อน <a href="<?= BASE_URL ?>/employer/jobpost.php">สร้างประกาศงาน</a></div>
    <?php else: ?>
        <section class="candidate-latest-jobs mb-4" aria-labelledby="candidate-latest-jobs-title">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <p class="eyebrow mb-1">YOUR LATEST POSTS</p>
                    <h2 id="candidate-latest-jobs-title" class="h4 mb-0">เลือกงานที่ต้องการจับคู่</h2>
                </div>
                <a class="small link-primary text-decoration-none" href="<?= BASE_URL ?>/employer/dashboard.php">ดูประกาศทั้งหมด <span aria-hidden="true">→</span></a>
            </div>
            <div class="row g-3">
                <?php foreach ($latestJobs as $job): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <a class="card h-100 border-0 shadow-sm candidate-job-switch <?= $selectedJobId === (int) $job['job_id'] ? 'is-selected' : '' ?>" href="<?= BASE_URL ?>/employer/candidates.php?job=<?= (int) $job['job_id'] ?>" <?= $selectedJobId === (int) $job['job_id'] ? 'aria-current="page"' : '' ?>>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between gap-2 mb-2"><span class="badge rounded-pill text-bg-primary">กำลังเปิดรับ</span><small class="text-secondary"><?= date('d/m/Y', strtotime($job['created_at'])) ?></small></div>
                                <h3 class="h6 mb-2"><?= e($job['job_title']) ?></h3>
                                <p class="small text-secondary mb-0">⌖ <?= e($job['work_location']) ?> · <?= pay_text($job) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <search class="card border-0 shadow-sm candidate-filter-panel mb-4" aria-label="ค้นหาและกรองผู้หางาน">
            <form method="get">
                <div class="card-body p-4">
                    <fieldset>
                        <legend class="h5 mb-3">ค้นหาและกรองผู้หางาน</legend>
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4">
                                <label class="form-label" for="job">จับคู่กับประกาศ</label>
                                <select class="form-select" id="job" name="job">
                                    <?php foreach ($employerJobs as $job): ?>
                                        <option value="<?= $job['job_id'] ?>" <?= $selectedJobId === (int) $job['job_id'] ? 'selected' : '' ?>><?= e($job['job_title']) ?><?= $job['work_interest_name'] ? ' · ' . e($job['work_interest_name']) : ' · ยังไม่เลือกหมวด' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label" for="q">ชื่อ คำโปรย หรือความสามารถ</label>
                                <input class="form-control" id="q" name="q" type="search" value="<?= e($_GET['q'] ?? '') ?>" placeholder="เช่น Excel, Canva" enterkeyhint="search">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="min_score">คะแนนขั้นต่ำ</label>
                                <select class="form-select" id="min_score" name="min_score">
                                    <?php foreach ([0 => 'ทั้งหมด', 50 => '50%+', 70 => '70%+', 90 => '90%+'] as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $minimumScore === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                                <a class="btn btn-light border" href="<?= BASE_URL ?>/employer/candidates.php?job=<?= $selectedJobId ?>">ล้างตัวกรอง</a>
                                <button class="btn btn-primary px-4" type="submit">ค้นหาผู้หางาน</button>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </form>
        </search>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <p class="eyebrow mb-1">MATCHED TALENT</p>
                <h2 class="h4 mb-0">พบ <?= $totalWorkers ?> โปรไฟล์<?= $selectedJob ? ' สำหรับ ' . e($selectedJob['job_title']) : '' ?></h2>
            </div>
            <span class="text-secondary small">แสดงเฉพาะผู้ที่อนุญาตให้ค้นหา</span>
        </div>

        <div class="row g-4">
            <?php foreach ($workers as $worker): ?>
                <div class="col-12 col-lg-6">
                    <article class="card h-100 border-0 shadow-sm candidate-card candidate-talent-card">
                        <div class="card-body p-4">
                            <header class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <?php if ($worker['profile_image_path']): ?>
                                        <img class="applicant-avatar-image" src="<?= BASE_URL . '/' . e($worker['profile_image_path']) ?>" alt="รูปโปรไฟล์ <?= e($worker['name']) ?>" width="56" height="56" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <div class="applicant-avatar"><?= e(mb_substr($worker['name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                    <div class="min-w-0"><h3 class="h5 mb-1 text-truncate"><?= e($worker['name']) ?></h3><p class="text-secondary mb-0 candidate-headline"><?= e($worker['headline'] ?: 'ยังไม่ได้ระบุคำโปรยแนะนำตัว') ?></p></div>
                                </div>
                                <?php if ($worker['match']['score'] !== null): ?>
                                    <span class="match-score flex-shrink-0"><?= $worker['match']['score'] ?>%</span>
                                <?php else: ?>
                                    <span class="badge text-bg-light border flex-shrink-0">ข้อมูลไม่พอ</span>
                                <?php endif; ?>
                            </header>

                            <div class="candidate-label">งานที่สนใจ</div>
                            <div class="skills-wrap mb-3"><?php foreach (matching_names($worker['work_interest_names']) as $interest): ?><span class="skill-tag"><?= e($interest) ?></span><?php endforeach ?><?php if (!$worker['work_interest_names']): ?><span class="text-secondary small">ยังไม่ได้ระบุ</span><?php endif ?></div>

                            <div class="candidate-label">ความสามารถเด่น</div>
                            <div class="skills-wrap mb-3"><?php foreach (matching_names($worker['skill_names']) as $skill): ?><span class="skill-tag"><?= e($skill) ?></span><?php endforeach ?><?php if (!$worker['skill_names']): ?><span class="text-secondary small">ยังไม่ได้ระบุความสามารถ</span><?php endif ?></div>

                            <p class="small text-secondary mb-2">⌖ <?= e($worker['work_province'] ?: '-') ?> · <?= e(['any' => 'ได้ทั้งหมด', 'onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid'][$worker['preferred_work_mode']] ?? $worker['preferred_work_mode']) ?></p>
                            <?php if ($worker['match']['reasons']): ?><ul class="match-reasons"><?php foreach ($worker['match']['reasons'] as $reason): ?><li><?= e($reason) ?></li><?php endforeach ?></ul><?php endif ?>

                            <footer class="candidate-card-actions mt-auto pt-3 border-top">
                                <a class="btn candidate-profile-button" href="<?= BASE_URL ?>/employer/candidate.php?job=<?= $selectedJobId ?>&worker=<?= $worker['user_id'] ?>">ดูคุณสมบัติ <span aria-hidden="true">→</span></a>
                                <?php if ($worker['application_status'] === 'withdrawn'): ?>
                                    <span class="badge text-bg-secondary candidate-state-badge">ถอนใบสมัครแล้ว</span>
                                <?php elseif ($worker['has_applied']): ?>
                                    <span class="badge text-bg-success candidate-state-badge">สมัครงานแล้ว</span>
                                <?php elseif ($worker['invitation_status']): ?>
                                    <span class="badge text-bg-light border candidate-state-badge">เชิญแล้ว: <?= e($worker['invitation_status']) ?></span>
                                <?php else: ?>
                                    <form method="post" class="candidate-invite-form"><?= csrf_field() ?><input type="hidden" name="job_id" value="<?= $selectedJobId ?>"><input type="hidden" name="worker_id" value="<?= $worker['user_id'] ?>"><button class="btn candidate-invite-button" type="submit">เชิญให้สมัคร</button></form>
                                <?php endif; ?>
                            </footer>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$workers): ?><div class="card border-0 shadow-sm candidate-empty-state"><div class="card-body p-5 text-center text-secondary">ไม่พบผู้หางานตามเงื่อนไข ลองลดตัวกรองหรือเพิ่มความสามารถที่ต้องการในประกาศ</div></div><?php endif ?>
        <?php if ($totalPages > 1): ?><nav class="mt-5" aria-label="หน้าผลการค้นหาผู้หางาน"><ul class="pagination justify-content-center flex-wrap"><?php for ($number = 1; $number <= $totalPages; $number++): $pageQuery = http_build_query(array_merge($_GET, ['page' => $number])); ?><li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($pageQuery) ?>"><?= $number ?></a></li><?php endfor ?></ul></nav><?php endif ?>
    <?php endif; ?>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
