<?php require_once __DIR__ . '/../config/config.php';
require_login('employer');
$jobId = (int)($_GET['job'] ?? 0);
$pdo = db();
$s = $pdo->prepare('SELECT job_id AS id,job_title AS title FROM jobs WHERE job_id=? AND employer_user_id=?');
$s->execute([$jobId, user()['id']]);
$job = $s->fetch();
if (!$job) redirect('employer/dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['status'], ['submitted', 'eligible', 'not_selected'], true)) {
    try { verify_csrf(); } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('employer/applicants.php?job=' . $jobId); }
    $updatedAppId = (int)$_POST['application_id'];
    $pdo->prepare('UPDATE applications SET application_status=? WHERE application_id=? AND job_id=?')->execute([$_POST['status'], $updatedAppId, $jobId]);
    notify_worker_status($updatedAppId);
    flash('success', 'อัปเดตสถานะผู้สมัครแล้ว');
    redirect('employer/applicants.php?job=' . $jobId);
}
$s = $pdo->prepare("SELECT a.application_id AS id,a.application_status AS status,a.cover_note,a.resume_file_path AS application_resume_file,CONCAT(u.first_name,' ',u.last_name) AS name,u.email,u.phone,wp.professional_headline AS headline,wp.biography,wp.skills,wp.resume_file_path AS profile_resume_file,wp.portfolio_file_path,wp.portfolio_url FROM applications a JOIN users u ON u.user_id=a.worker_user_id LEFT JOIN worker_profiles wp ON wp.user_id=u.user_id WHERE a.job_id=? ORDER BY a.created_at DESC");
$s->execute([$jobId]);
$apps = $s->fetchAll();
$pageTitle = 'ผู้สมัคร | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="dashboard narrow"><a class="back-link" href="dashboard.php">← กลับแดชบอร์ด</a>
    <div class="dashboard-title">
        <div>
            <p class="eyebrow">APPLICANTS</p>
            <h1><?= e($job['title']) ?></h1>
            <p><?= count($apps) ?> ผู้สมัคร</p>
        </div>
    </div>
    <section class="panel"><?php foreach ($apps as $app): ?><article class="applicant">
                <div class="applicant-avatar"><?= e(mb_substr($app['name'], 0, 1)) ?></div>
                <div class="applicant-info">
                    <h3><?= e($app['name']) ?></h3>
                    <p class="muted">อีเมล: <?= e($app['email']) ?> · โทร: <?= e($app['phone'] ?: '-') ?></p>
                    <p class="muted">ทักษะ: <?= e($app['skills'] ?: '-') ?></p>
                    <?php if ($app['biography']): ?><p class="muted">แนะนำตัว: <?= e(mb_substr($app['biography'], 0, 80)) ?><?= mb_strlen($app['biography']) > 80 ? '...' : '' ?></p><?php endif ?>
                    <?php if ($app['cover_note']): ?><blockquote><?= e(mb_substr($app['cover_note'], 0, 100)) ?><?= mb_strlen($app['cover_note']) > 100 ? '...' : '' ?></blockquote><?php endif ?>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/employer/applicant-detail.php?id=<?= $app['id'] ?>&job=<?= $jobId ?>">ดูโปรไฟล์เต็ม →</a>
                        <?php $resumeFile = $app['application_resume_file'] ?: $app['profile_resume_file']; ?>
                        <?php if ($resumeFile): ?><a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_resume&id=<?= $app['id'] ?>">เปิด Resume</a><?php endif ?>
                        <?php if ($app['portfolio_url']): ?><a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= e($app['portfolio_url']) ?>">Portfolio URL</a><?php endif ?>
                    </div>
                </div>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="application_id" value="<?= $app['id'] ?>"><select name="status">
                        <option value="submitted" <?= $app['status'] === 'submitted' ? 'selected' : '' ?>>รอพิจารณา</option>
                        <option value="eligible" <?= $app['status'] === 'eligible' ? 'selected' : '' ?>>มีสิทธิ์สัมภาษณ์</option>
                        <option value="not_selected" <?= $app['status'] === 'not_selected' ? 'selected' : '' ?>>ไม่ผ่าน</option>
                    </select><button class="btn btn-primary btn-sm">บันทึก</button></form>
            </article><?php endforeach ?><?php if (!$apps): ?><div class="empty">ยังไม่มีผู้สมัคร</div><?php endif ?></section>
</main><?php require APP_ROOT . '/partials/footer.php'; ?>
