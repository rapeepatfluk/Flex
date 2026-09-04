<?php
require_once __DIR__ . '/../config/config.php';
require_login('employer');
$pdo = db();
$employerId = (int) user()['id'];
$jobId = (int) ($_GET['job'] ?? 0);
$workerId = (int) ($_GET['worker'] ?? 0);
if (!matching_employer_is_verified($pdo, $employerId)) redirect('employer/candidates.php');
$jobStmt = $pdo->prepare("SELECT job_title FROM jobs WHERE job_id=? AND employer_user_id=? AND work_province=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
$jobStmt->execute([$jobId, $employerId, FLEXJOB_PROVINCE]);
$jobTitle = $jobStmt->fetchColumn();
if (!$jobTitle) {
    flash('error', 'ไม่พบประกาศงานที่เปิดรับสมัคร');
    redirect('employer/candidates.php');
}
$candidate = null;
foreach (matching_workers_for_job($pdo, $jobId, $employerId) as $worker) {
    if ((int) $worker['user_id'] === $workerId) { $candidate = $worker; break; }
}
if (!$candidate) {
    flash('error', 'ไม่พบโปรไฟล์หรือผู้หางานไม่ได้เปิดให้ค้นหา');
    redirect('employer/candidates.php?job=' . $jobId);
}
$profileUploadIsAvailable = static function (?string $path): bool {
    if (!$path) return false;
    $uploadsRoot = realpath(APP_ROOT . '/uploads');
    $absolutePath = realpath(APP_ROOT . '/' . ltrim($path, '/\\'));
    return $uploadsRoot && $absolutePath && str_starts_with(strtolower($absolutePath), strtolower($uploadsRoot . DIRECTORY_SEPARATOR)) && is_file($absolutePath);
};
$resumeAvailable = $profileUploadIsAvailable($candidate['resume_file_path'] ?? null);
$portfolioFileAvailable = $profileUploadIsAvailable($candidate['portfolio_file_path'] ?? null);
$portfolioUrl = trim((string) ($candidate['portfolio_url'] ?? ''));
$pageTitle = 'คุณสมบัติผู้หางาน | FLEXJOB';
$pageStyles = ['matching'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container py-5"><a class="link-primary d-inline-block mb-4" href="<?= BASE_URL ?>/employer/candidates.php?job=<?= $jobId ?>">← กลับไปผลการค้นหา</a>
    <div class="row g-4"><div class="col-lg-8"><section class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <div class="d-flex justify-content-between gap-3"><div class="d-flex align-items-center gap-3"><?php if ($candidate['profile_image_path']): ?><img class="applicant-avatar-image applicant-avatar-image-lg" src="<?= BASE_URL . '/' . e($candidate['profile_image_path']) ?>" alt="รูปโปรไฟล์ <?= e($candidate['name']) ?>"><?php else: ?><div class="applicant-avatar applicant-avatar-lg"><?= e(mb_substr($candidate['name'], 0, 1)) ?></div><?php endif ?><div><p class="eyebrow">CANDIDATE PROFILE</p><h1 class="h2 mb-1"><?= e($candidate['name']) ?></h1><p class="text-secondary mb-0"><?= e($candidate['headline'] ?: 'ยังไม่ได้ระบุคำโปรยแนะนำตัว') ?></p></div></div><?php if ($candidate['match']['score'] !== null): ?><span class="match-score large"><?= $candidate['match']['score'] ?>%</span><?php endif ?></div>
        <h2 class="h5 mt-4">งานที่สนใจ</h2><div class="skills-wrap mb-4"><?php foreach (matching_names($candidate['work_interest_names']) as $interest): ?><span class="skill-tag"><?= e($interest) ?></span><?php endforeach ?><?php if (!$candidate['work_interest_names']): ?><span class="text-secondary">ยังไม่ได้ระบุงานที่สนใจ</span><?php endif ?></div>
        <h2 class="h5">ความสามารถ</h2><div class="skills-wrap mb-4"><?php foreach (matching_names($candidate['skill_names']) as $skill): ?><span class="skill-tag"><?= e($skill) ?></span><?php endforeach ?><?php if (!$candidate['skill_names']): ?><span class="text-secondary">ยังไม่ได้ระบุความสามารถ</span><?php endif ?></div>
        <h2 class="h5">แนะนำตัว</h2><p class="text-secondary lh-lg"><?= nl2br(e($candidate['biography'] ?: 'ยังไม่ได้ระบุข้อมูลแนะนำตัว')) ?></p>
        <div class="row g-3 mt-2"><div class="col-md-6"><div class="border rounded p-3"><small class="text-secondary d-block">พื้นที่ที่สะดวก</small><b><?= e($candidate['work_province'] ?: '-') ?></b></div></div><div class="col-md-6"><div class="border rounded p-3"><small class="text-secondary d-block">พร้อมเริ่มงาน</small><b><?= e($candidate['available_from'] ? date('d/m/Y', strtotime($candidate['available_from'])) : 'ไม่ระบุ') ?></b></div></div></div>
        <?php if ($resumeAvailable || $portfolioFileAvailable || $portfolioUrl): ?>
            <section class="border rounded-4 p-3 p-md-4 mt-4" aria-labelledby="candidate-documents-heading">
                <p class="eyebrow mb-1">DOCUMENTS & PORTFOLIO</p>
                <h2 class="h5 mb-2" id="candidate-documents-heading">Resume และ Portfolio</h2>
                <p class="small text-secondary mb-3">ผู้หางานอนุญาตให้ผู้ว่าจ้างที่ยืนยันบัญชีและมีประกาศงานเปิดรับดูเอกสารเหล่านี้ได้</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($resumeAvailable): ?><a class="btn btn-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=candidate_resume&amp;worker=<?= $workerId ?>&amp;job=<?= $jobId ?>">เปิด Resume</a><?php endif; ?>
                    <?php if ($portfolioFileAvailable): ?><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=candidate_portfolio&amp;worker=<?= $workerId ?>&amp;job=<?= $jobId ?>">เปิด Portfolio</a><?php endif; ?>
                    <?php if ($portfolioUrl): ?><a class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer" href="<?= e($portfolioUrl) ?>">Portfolio URL <span aria-hidden="true">↗</span></a><?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="alert alert-light border mt-4 mb-0 small">ผู้หางานรายนี้ยังไม่มี Resume หรือ Portfolio ที่พร้อมเปิดดู</div>
        <?php endif; ?>
    </div></section></div>
    <div class="col-lg-4"><aside class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h5">Match กับงาน</h2><p class="fw-semibold"><?= e((string) $jobTitle) ?></p><?php if ($candidate['match']['reasons']): ?><ul class="match-reasons"><?php foreach ($candidate['match']['reasons'] as $reason): ?><li><?= e($reason) ?></li><?php endforeach ?></ul><?php endif ?><?php if ($candidate['match']['missing_required']): ?><p class="small text-secondary">ความสามารถจำเป็นที่ยังไม่ระบุ: <?= e(implode(', ', $candidate['match']['missing_required'])) ?></p><?php endif ?>
        <?php if ($candidate['application_status'] === 'withdrawn'): ?><div class="alert alert-secondary mb-0">ผู้หางานถอนใบสมัครนี้แล้ว</div><?php elseif ($candidate['has_applied']): ?><div class="alert alert-success mb-0">ผู้หางานสมัครงานนี้แล้ว</div><?php elseif ($candidate['invitation_status']): ?><div class="alert alert-info mb-0">ส่งคำเชิญแล้ว: <?= e($candidate['invitation_status']) ?></div><?php else: ?><form method="post" action="<?= BASE_URL ?>/employer/candidates.php"><input type="hidden" name="job_id" value="<?= $jobId ?>"><input type="hidden" name="worker_id" value="<?= $workerId ?>"><?= csrf_field() ?><label class="form-label" for="message">ข้อความถึงผู้หางาน (ไม่บังคับ)</label><textarea class="form-control mb-3" id="message" name="message" rows="4"></textarea><button class="btn btn-success w-100" type="submit">เชิญให้สมัครงาน</button></form><?php endif ?>
    </div></aside></div></div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
