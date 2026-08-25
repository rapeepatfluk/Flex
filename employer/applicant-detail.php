<?php require_once __DIR__ . '/../config/config.php';
require_login('employer');
$appId = (int)($_GET['id'] ?? 0);
$jobId = (int)($_GET['job'] ?? 0);
$pdo = db();

// Verify the job belongs to the current employer
$s = $pdo->prepare('SELECT job_id, job_title FROM jobs WHERE job_id=? AND employer_user_id=?');
$s->execute([$jobId, user()['id']]);
$job = $s->fetch();
if (!$job) redirect('employer/dashboard.php');

// Fetch the specific applicant
$s = $pdo->prepare("
    SELECT
        a.application_id AS id,
        a.application_status AS status,
        a.cover_note,
        a.resume_file_path AS application_resume,
        a.created_at,
        CONCAT(u.first_name,' ',u.last_name) AS name,
        u.email,
        u.phone,
        wp.professional_headline AS headline,
        wp.biography,
        wp.skills,
        wp.resume_file_path AS profile_resume,
        wp.portfolio_file_path,
        wp.portfolio_url
    FROM applications a
    JOIN users u ON u.user_id = a.worker_user_id
    LEFT JOIN worker_profiles wp ON wp.user_id = u.user_id
    WHERE a.application_id = ? AND a.job_id = ?
");
$s->execute([$appId, $jobId]);
$app = $s->fetch();
if (!$app) redirect('employer/applicants.php?job=' . $jobId);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['status'], ['submitted', 'eligible', 'not_selected'], true)) {
    try { verify_csrf(); } catch (RuntimeException $e) { flash('error', $e->getMessage()); redirect('employer/applicant-detail.php?id=' . $appId . '&job=' . $jobId); }
    $pdo->prepare('UPDATE applications SET application_status=? WHERE application_id=? AND job_id=?')
        ->execute([$_POST['status'], $appId, $jobId]);
    notify_worker_status($appId);
    flash('success', 'อัปเดตสถานะผู้สมัครแล้ว');
    redirect('employer/applicant-detail.php?id=' . $appId . '&job=' . $jobId);
}

$statusLabel = ['submitted' => 'รอพิจารณา', 'eligible' => 'มีสิทธิ์สัมภาษณ์', 'not_selected' => 'ไม่ผ่าน'];
$pageTitle = 'โปรไฟล์ผู้สมัคร | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="detail">
    <a class="back-link" href="<?= BASE_URL ?>/employer/applicants.php?job=<?= $jobId ?>">← กลับรายชื่อผู้สมัคร</a>

    <div class="app-detail-header">
        <div class="app-detail-hero">
            <div class="applicant-avatar applicant-avatar-lg"><?= e(mb_substr($app['name'], 0, 1)) ?></div>
            <div>
                <p class="eyebrow">ผู้สมัครงาน · <?= e($job['job_title']) ?></p>
                <h1><?= e($app['name']) ?></h1>
                <?php if ($app['headline']): ?>
                <p class="company-line"><?= e($app['headline']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <span class="status <?= $app['status'] ?> status-lg"><?= $statusLabel[$app['status']] ?? $app['status'] ?></span>
    </div>

    <div class="app-detail-grid">

        <!-- Left: Profile Info -->
        <div class="app-detail-left">

            <!-- Contact Info -->
            <div class="panel">
                <h2>ข้อมูลติดต่อ</h2>
                <div class="contact-list">
                    <div class="contact-item-row">
                        <span class="contact-icon">📧</span>
                        <div>
                            <small class="muted">อีเมล</small>
                            <a href="mailto:<?= e($app['email']) ?>" class="contact-val"><?= e($app['email']) ?></a>
                        </div>
                    </div>
                    <div class="contact-item-row">
                        <span class="contact-icon">📞</span>
                        <div>
                            <small class="muted">โทรศัพท์</small>
                            <a href="tel:<?= e($app['phone']) ?>" class="contact-val"><?= e($app['phone'] ?: '-') ?></a>
                        </div>
                    </div>
                    <div class="contact-item-row">
                        <span class="contact-icon">📅</span>
                        <div>
                            <small class="muted">วันที่สมัคร</small>
                            <span class="contact-val"><?= date('d/m/Y H:i', strtotime($app['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills -->
            <?php if ($app['skills']): ?>
            <div class="panel mt-16">
                <h2>ทักษะ</h2>
                <div class="skills-wrap">
                    <?php foreach (array_filter(array_map('trim', explode(',', $app['skills']))) as $skill): ?>
                    <span class="skill-tag"><?= e($skill) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bio -->
            <?php if ($app['biography']): ?>
            <div class="panel mt-16">
                <h2>แนะนำตัว</h2>
                <p class="muted" style="font-size:14px;line-height:1.8"><?= nl2br(e($app['biography'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Cover Note -->
            <?php if ($app['cover_note']): ?>
            <div class="panel mt-16">
                <h2>จดหมายแนะนำตัว</h2>
                <blockquote class="cover-note-quote"><?= nl2br(e($app['cover_note'])) ?></blockquote>
            </div>
            <?php endif; ?>

            <!-- Documents -->
            <?php $resumeFile = $app['application_resume'] ?: $app['profile_resume']; ?>
            <?php if ($resumeFile || $app['portfolio_file_path'] || $app['portfolio_url']): ?>
            <div class="panel mt-16">
                <h2>เอกสารและพอร์ตโฟลิโอ</h2>
                <div class="doc-links">
                    <?php if ($resumeFile): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_resume&id=<?= $app['id'] ?>">📄 Resume</a>
                    <?php endif; ?>
                    <?php if ($app['portfolio_file_path']): ?>
                    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_portfolio&id=<?= $app['id'] ?>">🗂 Portfolio ไฟล์</a>
                    <?php endif; ?>
                    <?php if ($app['portfolio_url']): ?>
                    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?= e($app['portfolio_url']) ?>">🔗 Portfolio URL</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Status Management -->
        <div class="app-detail-right">
            <div class="panel">
                <h2>จัดการสถานะ</h2>
                <p class="muted" style="font-size:13px;margin-top:0">เปลี่ยนสถานะผู้สมัครและบันทึกผลการพิจารณา</p>

                <div class="status-options">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                        <label class="status-radio <?= $app['status'] === 'submitted' ? 'active' : '' ?>">
                            <input type="radio" name="status" value="submitted" <?= $app['status'] === 'submitted' ? 'checked' : '' ?>>
                            <div class="status-radio-content">
                                <span class="status-icon">⏳</span>
                                <div>
                                    <b>รอพิจารณา</b>
                                    <small>ยังไม่ได้ตัดสินใจ</small>
                                </div>
                            </div>
                        </label>

                        <label class="status-radio eligible <?= $app['status'] === 'eligible' ? 'active' : '' ?>">
                            <input type="radio" name="status" value="eligible" <?= $app['status'] === 'eligible' ? 'checked' : '' ?>>
                            <div class="status-radio-content">
                                <span class="status-icon">✅</span>
                                <div>
                                    <b>มีสิทธิ์สัมภาษณ์</b>
                                    <small>ผู้สมัครผ่านการคัดเลือกเบื้องต้น</small>
                                </div>
                            </div>
                        </label>

                        <label class="status-radio not-selected-opt <?= $app['status'] === 'not_selected' ? 'active' : '' ?>">
                            <input type="radio" name="status" value="not_selected" <?= $app['status'] === 'not_selected' ? 'checked' : '' ?>>
                            <div class="status-radio-content">
                                <span class="status-icon">❌</span>
                                <div>
                                    <b>ไม่ผ่านการคัดเลือก</b>
                                    <small>ผู้สมัครไม่ผ่านเกณฑ์</small>
                                </div>
                            </div>
                        </label>

                        <button class="btn btn-primary full-width" style="margin-top:16px">บันทึกสถานะ</button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="panel mt-16">
                <h2>ดำเนินการด่วน</h2>
                <div class="quick-actions">
                    <a href="mailto:<?= e($app['email']) ?>?subject=เรื่อง: ใบสมัครงาน <?= e($job['job_title']) ?>" class="quick-action-btn">
                        <span>✉️</span>
                        <div>
                            <b>ส่งอีเมล</b>
                            <small>ติดต่อผู้สมัครโดยตรง</small>
                        </div>
                    </a>
                    <?php if ($app['phone']): ?>
                    <a href="tel:<?= e($app['phone']) ?>" class="quick-action-btn">
                        <span>📞</span>
                        <div>
                            <b>โทรหา</b>
                            <small><?= e($app['phone']) ?></small>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
