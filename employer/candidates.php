<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');

$pdo = db();
$employerId = (int) user()['id'];
$verified = matching_employer_is_verified($pdo, $employerId);
$jobStatement = $pdo->prepare("SELECT j.job_id,j.job_title,wi.interest_name work_interest_name FROM jobs j LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id WHERE j.employer_user_id=? AND j.work_province=? AND j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.created_at DESC");
$jobStatement->execute([$employerId, FLEXJOB_PROVINCE]);
$employerJobs = $jobStatement->fetchAll();
$selectedJobId = (int) ($_GET['job'] ?? $_POST['job_id'] ?? ($employerJobs[0]['job_id'] ?? 0));
$ownedJobIds = array_map('intval', array_column($employerJobs, 'job_id'));
if ($selectedJobId && !in_array($selectedJobId, $ownedJobIds, true)) $selectedJobId = 0;

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
$pageStyles = ['matching'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div><p class="eyebrow">TALENT MATCHING</p><h1 class="h2 mb-1">ค้นหาผู้หางานที่เหมาะกับงาน</h1><p class="text-secondary mb-0">คะแนนใช้ช่วยเรียงลำดับ ไม่ได้ตัดสิทธิ์ผู้หางานอัตโนมัติ</p></div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/employer/dashboard.php">กลับไปจัดการประกาศ</a>
    </div>

    <?php if (!$verified): ?>
        <div class="alert alert-warning">บัญชีของคุณต้องผ่านการยืนยันก่อนค้นหาโปรไฟล์ผู้หางาน</div>
    <?php elseif (!$employerJobs): ?>
        <div class="alert alert-info">คุณต้องมีประกาศงานที่เปิดรับสมัครก่อน <a href="<?= BASE_URL ?>/employer/jobpost.php">สร้างประกาศงาน</a></div>
    <?php else: ?>
        <form class="card border-0 shadow-sm mb-4" method="get"><div class="card-body"><div class="row g-3">
            <div class="col-lg-4"><label class="form-label" for="job">จับคู่กับประกาศ</label><select class="form-select" id="job" name="job" onchange="this.form.submit()"><?php foreach ($employerJobs as $job): ?><option value="<?= $job['job_id'] ?>" <?= $selectedJobId === (int) $job['job_id'] ? 'selected' : '' ?>><?= e($job['job_title']) ?><?= $job['work_interest_name'] ? ' · ' . e($job['work_interest_name']) : ' · ยังไม่เลือกหมวด' ?></option><?php endforeach ?></select></div>
            <div class="col-lg-6"><label class="form-label" for="q">ชื่อ Headline หรือทักษะ</label><input class="form-control" id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="เช่น Excel, Canva"></div>
            <div class="col-lg-2"><label class="form-label" for="min_score">คะแนนขั้นต่ำ</label><select class="form-select" id="min_score" name="min_score"><?php foreach ([0 => 'ทั้งหมด', 50 => '50%+', 70 => '70%+', 90 => '90%+'] as $value => $label): ?><option value="<?= $value ?>" <?= $minimumScore === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select></div>
            <div class="col-12 d-flex justify-content-end"><button class="btn btn-success" type="submit">ค้นหา</button></div>
        </div></div></form>

        <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">พบ <?= $totalWorkers ?> โปรไฟล์</h2><span class="text-secondary small">แสดงเฉพาะผู้ที่อนุญาตและไม่เปิดข้อมูลติดต่อ</span></div>
        <div class="row g-4">
            <?php foreach ($workers as $worker): ?><div class="col-12 col-lg-6"><article class="card h-100 border-0 shadow-sm candidate-card"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3"><div class="d-flex align-items-center gap-3"><?php if ($worker['profile_image_path']): ?><img class="applicant-avatar-image" src="<?= BASE_URL . '/' . e($worker['profile_image_path']) ?>" alt="รูปโปรไฟล์ <?= e($worker['name']) ?>"><?php else: ?><div class="applicant-avatar"><?= e(mb_substr($worker['name'], 0, 1)) ?></div><?php endif ?><div><h3 class="h5 mb-1"><?= e($worker['name']) ?></h3><p class="text-secondary mb-2"><?= e($worker['headline'] ?: 'ยังไม่ได้ระบุคำโปรยแนะนำตัว') ?></p></div></div><?php if ($worker['match']['score'] !== null): ?><span class="match-score"><?= $worker['match']['score'] ?>%</span><?php else: ?><span class="badge text-bg-light border">ข้อมูลไม่พอ</span><?php endif ?></div>
                <div class="small fw-semibold mb-2">งานที่สนใจ</div><div class="skills-wrap mb-3"><?php foreach (matching_names($worker['work_interest_names']) as $interest): ?><span class="skill-tag"><?= e($interest) ?></span><?php endforeach ?><?php if (!$worker['work_interest_names']): ?><span class="text-secondary small">ยังไม่ได้ระบุ</span><?php endif ?></div>
                <div class="skills-wrap mb-3"><?php foreach (matching_names($worker['skill_names']) as $skill): ?><span class="skill-tag"><?= e($skill) ?></span><?php endforeach ?><?php if (!$worker['skill_names']): ?><span class="text-secondary small">ยังไม่ได้ระบุทักษะ</span><?php endif ?></div>
                <p class="small text-secondary mb-2">พื้นที่: <?= e($worker['work_province'] ?: '-') ?> · รูปแบบงาน: <?= e(['any' => 'ได้ทั้งหมด','onsite' => 'On-site','remote' => 'Remote','hybrid' => 'Hybrid'][$worker['preferred_work_mode']] ?? $worker['preferred_work_mode']) ?></p>
                <?php if ($worker['match']['reasons']): ?><ul class="match-reasons"><?php foreach ($worker['match']['reasons'] as $reason): ?><li><?= e($reason) ?></li><?php endforeach ?></ul><?php endif ?>
                <div class="d-flex flex-wrap gap-2 mt-auto pt-3"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/employer/candidate.php?job=<?= $selectedJobId ?>&worker=<?= $worker['user_id'] ?>">ดูคุณสมบัติ</a><?php if ($worker['application_status'] === 'withdrawn'): ?><span class="badge text-bg-secondary align-self-center">ถอนใบสมัครแล้ว</span><?php elseif ($worker['has_applied']): ?><span class="badge text-bg-success align-self-center">สมัครงานแล้ว</span><?php elseif ($worker['invitation_status']): ?><span class="badge text-bg-light border align-self-center">เชิญแล้ว: <?= e($worker['invitation_status']) ?></span><?php else: ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="job_id" value="<?= $selectedJobId ?>"><input type="hidden" name="worker_id" value="<?= $worker['user_id'] ?>"><button class="btn btn-sm btn-success" type="submit">เชิญให้สมัคร</button></form><?php endif ?></div>
            </div></article></div><?php endforeach ?>
        </div>
        <?php if (!$workers): ?><div class="card border-0 shadow-sm"><div class="card-body p-5 text-center text-secondary">ไม่พบผู้หางานตามเงื่อนไข ลองลดตัวกรองหรือเพิ่มทักษะที่ต้องการในประกาศ</div></div><?php endif ?>
        <?php if ($totalPages > 1): ?><nav class="mt-5" aria-label="หน้าผลการค้นหาผู้หางาน"><ul class="pagination justify-content-center flex-wrap"><?php for ($number = 1; $number <= $totalPages; $number++): $pageQuery = http_build_query(array_merge($_GET, ['page' => $number])); ?><li class="page-item <?= $number === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($pageQuery) ?>"><?= $number ?></a></li><?php endfor ?></ul></nav><?php endif ?>
    <?php endif ?>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
