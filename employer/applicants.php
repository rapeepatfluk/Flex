<?php
require_once __DIR__ . '/../config/config.php';

require_login('employer');

$jobId = (int) ($_GET['job'] ?? 0);
$pdo = db();

$jobStatement = $pdo->prepare('SELECT job_id AS id,job_title AS title FROM jobs WHERE job_id=? AND employer_user_id=?');
$jobStatement->execute([$jobId, user()['id']]);
$job = $jobStatement->fetch();

if (!$job) {
    redirect('employer/dashboard.php');
}

$statusOptions = [
    'submitted' => ['label' => 'รอพิจารณา', 'tone' => 'warning'],
    'eligible' => ['label' => 'มีสิทธิ์สัมภาษณ์', 'tone' => 'primary'],
    'interview_passed' => ['label' => 'ผ่านสัมภาษณ์แล้ว', 'tone' => 'success'],
    'completed' => ['label' => 'งานเสร็จสิ้น', 'tone' => 'info'],
    'not_selected' => ['label' => 'ไม่ผ่าน', 'tone' => 'danger'],
    'withdrawn' => ['label' => 'ถอนใบสมัครแล้ว', 'tone' => 'secondary'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array((string) ($_POST['status'] ?? ''), ['submitted', 'eligible', 'interview_passed', 'completed', 'not_selected'], true)) {
    try {
        verify_csrf();
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect('employer/applicants.php?job=' . $jobId);
    }

    $updatedAppId = (int) ($_POST['application_id'] ?? 0);
    $update = $pdo->prepare("UPDATE applications SET application_status=? WHERE application_id=? AND job_id=? AND application_status<>'withdrawn'");
    $update->execute([$_POST['status'], $updatedAppId, $jobId]);

    if ($update->rowCount()) {
        notify_worker_status($updatedAppId);
        flash('success', 'อัปเดตสถานะผู้สมัครแล้ว');
    } else {
        flash('error', 'ไม่สามารถเปลี่ยนสถานะใบสมัครที่ผู้หางานถอนแล้ว');
    }

    redirect('employer/applicants.php?job=' . $jobId);
}

$applicantStatement = $pdo->prepare("SELECT
    a.application_id AS id,
    a.application_status AS status,
    a.cover_note,
    a.resume_file_path AS application_resume_file,
    a.created_at,
    CONCAT(u.first_name,' ',u.last_name) AS name,
    u.email,
    u.phone,
    wp.professional_headline AS headline,
    wp.biography,
    wp.profile_image_path,
    (SELECT GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR ', ') FROM worker_skills ws JOIN skills s ON s.skill_id=ws.skill_id WHERE ws.worker_user_id=a.worker_user_id) AS skills,
    wp.resume_file_path AS profile_resume_file,
    wp.portfolio_file_path,
    wp.portfolio_url
    FROM applications a
    JOIN users u ON u.user_id=a.worker_user_id
    LEFT JOIN worker_profiles wp ON wp.user_id=u.user_id
    WHERE a.job_id=?
    ORDER BY a.created_at DESC");
$applicantStatement->execute([$jobId]);
$apps = $applicantStatement->fetchAll();

$statusCounts = array_fill_keys(array_keys($statusOptions), 0);
foreach ($apps as $app) {
    if (isset($statusCounts[$app['status']])) {
        $statusCounts[$app['status']]++;
    }
}
$inProgressCount = $statusCounts['eligible'] + $statusCounts['interview_passed'];
$reviewCount = $statusCounts['submitted'];

$pageTitle = 'ผู้สมัคร | FLEXJOB';
$pageStyles = ['employer-applicants'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container employer-applicants-page py-4 py-lg-5">
    <a class="employer-applicants-back d-inline-flex align-items-center gap-2 mb-4" href="<?= BASE_URL ?>/employer/dashboard.php">← กลับแดชบอร์ด</a>

    <section class="card border-0 shadow-sm employer-applicants-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg">
                    <p class="employer-applicants-eyebrow mb-1">APPLICANTS</p>
                    <h1 class="h2 mb-2"><?= e($job['title']) ?></h1>
                    <p class="mb-0 text-secondary">ตรวจสอบโปรไฟล์ เอกสาร และอัปเดตผลการคัดเลือกผู้สมัครในหน้านี้</p>
                </div>
                <div class="col-lg-auto">
                    <div class="employer-applicants-total">
                        <strong><?= count($apps) ?></strong>
                        <span>ผู้สมัครทั้งหมด</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="สรุปสถานะผู้สมัคร">
        <div class="col-6 col-lg-3">
            <article class="card border-0 shadow-sm h-100 employer-applicant-stat">
                <div class="card-body p-3 p-lg-4">
                    <p class="mb-1 text-secondary">รอพิจารณา</p>
                    <strong class="h3 mb-0"><?= $reviewCount ?></strong>
                </div>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="card border-0 shadow-sm h-100 employer-applicant-stat">
                <div class="card-body p-3 p-lg-4">
                    <p class="mb-1 text-secondary">อยู่ระหว่างคัดเลือก</p>
                    <strong class="h3 mb-0"><?= $inProgressCount ?></strong>
                </div>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="card border-0 shadow-sm h-100 employer-applicant-stat">
                <div class="card-body p-3 p-lg-4">
                    <p class="mb-1 text-secondary">งานเสร็จสิ้น</p>
                    <strong class="h3 mb-0"><?= $statusCounts['completed'] ?></strong>
                </div>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="card border-0 shadow-sm h-100 employer-applicant-stat">
                <div class="card-body p-3 p-lg-4">
                    <p class="mb-1 text-secondary">ไม่ผ่าน/ถอนใบสมัคร</p>
                    <strong class="h3 mb-0"><?= $statusCounts['not_selected'] + $statusCounts['withdrawn'] ?></strong>
                </div>
            </article>
        </div>
    </section>

    <section aria-labelledby="applicantListHeading">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <p class="employer-applicants-eyebrow mb-1">CANDIDATE LIST</p>
                <h2 class="h3 mb-0" id="applicantListHeading">รายชื่อผู้สมัคร</h2>
            </div>
            <p class="mb-0 small text-secondary">เรียงตามวันที่สมัครล่าสุด</p>
        </div>

        <?php if ($apps): ?>
            <div class="row g-4">
                <?php foreach ($apps as $app):
                    $status = $statusOptions[$app['status']] ?? ['label' => $app['status'], 'tone' => 'secondary'];
                    $skills = array_slice(array_filter(array_map('trim', explode(',', (string) $app['skills']))), 0, 6);
                    $resumeFile = $app['application_resume_file'] ?: $app['profile_resume_file'];
                ?>
                    <div class="col-md-6">
                        <article class="card border-0 shadow-sm h-100 employer-applicant-card">
                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div class="d-flex align-items-center gap-3 min-w-0">
                                        <?php if ($app['profile_image_path']): ?>
                                            <img class="employer-applicant-avatar" src="<?= BASE_URL . '/' . e($app['profile_image_path']) ?>" alt="รูปโปรไฟล์ <?= e($app['name']) ?>" width="64" height="64" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <div class="employer-applicant-avatar employer-applicant-avatar-fallback" aria-hidden="true"><?= e(mb_substr($app['name'], 0, 1)) ?></div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <h3 class="h5 mb-1 text-break"><?= e($app['name']) ?></h3>
                                            <p class="small text-secondary mb-0"><?= e($app['headline'] ?: 'ผู้สมัคร FLEXJOB') ?></p>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill text-bg-<?= e($status['tone']) ?> text-nowrap"><?= e($status['label']) ?></span>
                                </div>

                                <dl class="row row-cols-1 row-cols-sm-2 g-2 small text-secondary mb-3 employer-applicant-meta">
                                    <div class="col">
                                        <dt>อีเมล</dt>
                                        <dd class="mb-0 text-break"><?= e($app['email']) ?></dd>
                                    </div>
                                    <div class="col">
                                        <dt>โทรศัพท์</dt>
                                        <dd class="mb-0"><?= e($app['phone'] ?: 'ยังไม่ได้ระบุ') ?></dd>
                                    </div>
                                </dl>

                                <?php if ($skills): ?>
                                    <ul class="list-unstyled d-flex flex-wrap gap-2 mb-3">
                                        <?php foreach ($skills as $skill): ?>
                                            <li><span class="badge employer-applicant-skill"><?= e($skill) ?></span></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if ($app['biography'] || $app['cover_note']): ?>
                                    <p class="small text-secondary employer-applicant-summary mb-3"><?= e(mb_substr($app['biography'] ?: $app['cover_note'], 0, 135)) ?><?= mb_strlen($app['biography'] ?: $app['cover_note']) > 135 ? '…' : '' ?></p>
                                <?php endif; ?>

                                <div class="d-flex flex-wrap gap-2 mt-auto pt-3 border-top">
                                    <a class="btn btn-primary btn-sm flex-grow-1" href="<?= BASE_URL ?>/employer/applicant-detail.php?id=<?= $app['id'] ?>&job=<?= $jobId ?>">ดูโปรไฟล์</a>
                                    <?php if ($resumeFile): ?>
                                        <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_resume&id=<?= $app['id'] ?>">Resume</a>
                                    <?php endif; ?>
                                    <?php if ($app['portfolio_url']): ?>
                                        <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="<?= e($app['portfolio_url']) ?>">Portfolio</a>
                                    <?php endif; ?>
                                </div>

                                <?php if ($app['status'] === 'withdrawn'): ?>
                                    <p class="small text-secondary mb-0 mt-3">ผู้หางานถอนใบสมัครแล้ว จึงไม่สามารถเปลี่ยนสถานะได้</p>
                                <?php else: ?>
                                    <form class="row g-2 align-items-end mt-3 pt-3 border-top" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                        <div class="col">
                                            <label class="form-label small fw-semibold mb-1" for="status-<?= $app['id'] ?>">ผลการพิจารณา</label>
                                            <select class="form-select form-select-sm" id="status-<?= $app['id'] ?>" name="status">
                                                <?php foreach ($statusOptions as $statusValue => $option): ?>
                                                    <?php if ($statusValue !== 'withdrawn'): ?>
                                                        <option value="<?= e($statusValue) ?>" <?= $app['status'] === $statusValue ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-primary btn-sm employer-status-save" type="submit">บันทึก</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <article class="card border-0 shadow-sm employer-applicants-empty">
                <div class="card-body text-center p-5">
                    <span class="employer-applicants-empty-icon" aria-hidden="true">◎</span>
                    <h3 class="h4 mt-3">ยังไม่มีผู้สมัคร</h3>
                    <p class="text-secondary mb-0">เมื่อมีผู้สมัครงานนี้ ข้อมูลและเอกสารของพวกเขาจะแสดงที่นี่</p>
                </div>
            </article>
        <?php endif; ?>
    </section>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
