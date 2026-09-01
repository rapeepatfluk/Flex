<?php
require_once __DIR__ . '/../config/config.php';

require_login('employer');

$appId = (int) ($_GET['id'] ?? 0);
$jobId = (int) ($_GET['job'] ?? 0);
$pdo = db();

$jobStatement = $pdo->prepare('SELECT j.job_id,j.job_title,u.email AS employer_email FROM jobs j JOIN users u ON u.user_id=j.employer_user_id WHERE j.job_id=? AND j.employer_user_id=?');
$jobStatement->execute([$jobId, user()['id']]);
$job = $jobStatement->fetch();

if (!$job) {
    redirect('employer/dashboard.php');
}

$applicantStatement = $pdo->prepare("
    SELECT
        a.application_id AS id,
        a.application_status AS status,
        a.worker_user_id,
        a.cover_note,
        a.resume_file_path AS application_resume,
        a.created_at,
        CONCAT(u.first_name,' ',u.last_name) AS name,
        u.email,
        u.phone,
        wp.professional_headline AS headline,
        wp.biography,
        wp.profile_image_path,
        (SELECT GROUP_CONCAT(s.skill_name ORDER BY s.skill_name SEPARATOR ', ') FROM worker_skills ws JOIN skills s ON s.skill_id=ws.skill_id WHERE ws.worker_user_id=a.worker_user_id) AS skills,
        wp.resume_file_path AS profile_resume,
        wp.portfolio_file_path,
        wp.portfolio_url
    FROM applications a
    JOIN users u ON u.user_id = a.worker_user_id
    LEFT JOIN worker_profiles wp ON wp.user_id = u.user_id
    WHERE a.application_id = ? AND a.job_id = ?
");
$applicantStatement->execute([$appId, $jobId]);
$app = $applicantStatement->fetch();

if (!$app) {
    redirect('employer/applicants.php?job=' . $jobId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_email') {
    try {
        verify_csrf();
        if ($app['status'] === 'withdrawn') {
            throw new RuntimeException('ไม่สามารถส่งอีเมลถึงผู้หางานที่ถอนใบสมัครแล้ว');
        }
        if (!mail_is_configured()) {
            throw new RuntimeException('ระบบยังไม่พร้อมส่งอีเมล กรุณาติดต่อผู้ดูแลระบบ');
        }

        $emailSubject = trim((string) ($_POST['email_subject'] ?? ''));
        $emailMessage = trim((string) ($_POST['email_message'] ?? ''));
        if ($emailSubject === '' || $emailMessage === '') {
            throw new RuntimeException('กรุณากรอกหัวข้อและข้อความให้ครบถ้วน');
        }
        if (mb_strlen($emailSubject) > 160) {
            throw new RuntimeException('หัวข้ออีเมลต้องไม่เกิน 160 ตัวอักษร');
        }
        if (mb_strlen($emailMessage) > 5000) {
            throw new RuntimeException('ข้อความอีเมลต้องไม่เกิน 5,000 ตัวอักษร');
        }
        if (!filter_var($app['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('อีเมลของผู้สมัครไม่ถูกต้อง');
        }

        $safeApplicantName = e($app['name']);
        $safeEmployerName = e((string) user()['name']);
        $safeJobTitle = e($job['job_title']);
        $safeMessage = nl2br(e($emailMessage));
        $emailBody = <<<HTML
<h2 style="margin:0 0 8px;font-size:22px;color:#17231f;">ข้อความจากผู้ว่าจ้าง</h2>
<p style="margin:0 0 20px;color:#697671;">สวัสดี {$safeApplicantName}</p>
<div style="margin-bottom:22px;padding:18px 20px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff;color:#334155;line-height:1.8;">{$safeMessage}</div>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;background:#f8fafc;border-radius:10px;">
  <tr><td style="padding:16px 18px;color:#64748b;font-size:13px;">ตำแหน่งงาน<br><strong style="color:#1e293b;font-size:15px;">{$safeJobTitle}</strong></td></tr>
</table>
<p style="margin:0;color:#64748b;font-size:13px;">ส่งโดย {$safeEmployerName} · สามารถกดตอบกลับอีเมลนี้เพื่อติดต่อผู้ว่าจ้างได้โดยตรง</p>
HTML;

        $sent = send_mail(
            $app['email'],
            $app['name'],
            $emailSubject,
            $emailBody,
            $job['employer_email'],
            (string) user()['name']
        );
        if (!$sent) {
            throw new RuntimeException('ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        flash('success', 'ส่งอีเมลถึง ' . $app['email'] . ' เรียบร้อยแล้ว');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log('Applicant email failed: ' . $e->getMessage());
        flash('error', 'ไม่สามารถส่งอีเมลได้ กรุณาลองใหม่อีกครั้ง');
    }

    redirect('employer/applicant-detail.php?id=' . $appId . '&job=' . $jobId);
}

$statusOptions = [
    'submitted' => ['label' => 'รอพิจารณา', 'description' => 'ยังไม่ได้ตัดสินใจ', 'tone' => 'warning', 'icon' => '◷'],
    'eligible' => ['label' => 'มีสิทธิ์สัมภาษณ์', 'description' => 'ผ่านการคัดเลือกเบื้องต้น', 'tone' => 'primary', 'icon' => '✓'],
    'interview_passed' => ['label' => 'ผ่านสัมภาษณ์แล้ว', 'description' => 'พร้อมเริ่มต้นขั้นตอนการทำงาน', 'tone' => 'success', 'icon' => '✦'],
    'completed' => ['label' => 'งานเสร็จสิ้น', 'description' => 'เปิดให้ทั้งสองฝ่ายให้คะแนนกันได้', 'tone' => 'info', 'icon' => '★'],
    'not_selected' => ['label' => 'ไม่ผ่านการคัดเลือก', 'description' => 'ผู้สมัครไม่ผ่านเกณฑ์', 'tone' => 'danger', 'icon' => '×'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($statusOptions[(string) ($_POST['status'] ?? '')])) {
    try {
        verify_csrf();
        $statusChanged = application_update_status_by_employer(
            $pdo,
            (int) user()['id'],
            $jobId,
            $appId,
            (string) $_POST['status']
        );

        if ($statusChanged) {
            notify_worker_status($appId);
            flash('success', 'อัปเดตสถานะผู้สมัครแล้ว');
        } else {
            flash('success', 'สถานะผู้สมัครไม่มีการเปลี่ยนแปลง');
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        error_log('Application status update failed: ' . $e->getMessage());
        flash('error', 'ไม่สามารถอัปเดตสถานะผู้สมัครได้ กรุณาลองใหม่อีกครั้ง');
    }
    redirect('employer/applicant-detail.php?id=' . $appId . '&job=' . $jobId);
}

$ratingSummaryStatement = $pdo->prepare('SELECT ROUND(AVG(rating_by_employer), 1) AS average, COUNT(rating_by_employer) AS count FROM applications WHERE worker_user_id=? AND rating_by_employer IS NOT NULL');
$ratingSummaryStatement->execute([$app['worker_user_id']]);
$workerRatingSummary = $ratingSummaryStatement->fetch() ?: ['average' => null, 'count' => 0];

$ratingSubmittedStatement = $pdo->prepare('SELECT rating_by_employer FROM applications WHERE application_id=?');
$ratingSubmittedStatement->execute([$appId]);
$employerRatingSubmitted = $ratingSubmittedStatement->fetchColumn() !== null;

$currentStatus = $statusOptions[$app['status']] ?? ['label' => 'ถอนใบสมัครแล้ว', 'description' => 'ผู้หางานถอนใบสมัครนี้แล้ว', 'tone' => 'secondary', 'icon' => '−'];
$resumeFile = $app['application_resume'] ?: $app['profile_resume'];
$skills = array_filter(array_map('trim', explode(',', (string) $app['skills'])));

$pageTitle = 'โปรไฟล์ผู้สมัคร | FLEXJOB';
$pageStyles = ['rating', 'employer-applicant-detail'];
require APP_ROOT . '/partials/header.php';
?>
<main class="container applicant-detail-page py-4 py-lg-5">
    <a class="applicant-detail-back d-inline-flex align-items-center gap-2 mb-4" href="<?= BASE_URL ?>/employer/applicants.php?job=<?= $jobId ?>">← กลับรายชื่อผู้สมัคร</a>

    <section class="card border-0 shadow-sm applicant-profile-hero mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg">
                    <div class="d-flex align-items-center gap-3 gap-lg-4">
                        <?php if ($app['profile_image_path']): ?>
                            <img class="applicant-profile-avatar" src="<?= BASE_URL . '/' . e($app['profile_image_path']) ?>" alt="รูปโปรไฟล์ <?= e($app['name']) ?>" width="96" height="96" fetchpriority="high" decoding="async">
                        <?php else: ?>
                            <div class="applicant-profile-avatar applicant-profile-avatar-fallback" aria-hidden="true"><?= e(mb_substr($app['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <p class="applicant-detail-eyebrow mb-1">APPLICANT · <?= e($job['job_title']) ?></p>
                            <h1 class="h2 mb-1 text-break"><?= e($app['name']) ?></h1>
                            <?php if ($app['headline']): ?>
                                <p class="mb-2 text-secondary"><?= e($app['headline']) ?></p>
                            <?php endif; ?>
                            <?php $ratingSummary = $workerRatingSummary; require APP_ROOT . '/partials/rating-summary.php'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-auto">
                    <span class="badge rounded-pill text-bg-<?= e($currentStatus['tone']) ?> applicant-status-badge">
                        <?= e($currentStatus['icon']) ?> <?= e($currentStatus['label']) ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="vstack gap-4">
                <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantContactHeading">
                    <div class="card-body p-4">
                        <p class="applicant-detail-eyebrow mb-1">CONTACT INFORMATION</p>
                        <h2 class="h4 mb-4" id="applicantContactHeading">ข้อมูลติดต่อ</h2>
                        <dl class="row row-cols-1 row-cols-md-3 g-3 mb-0 applicant-contact-grid">
                            <div class="col">
                                <div class="applicant-contact-item h-100">
                                    <dt>อีเมล</dt>
                                    <dd class="mb-0"><a class="link-primary text-break" href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a></dd>
                                </div>
                            </div>
                            <div class="col">
                                <div class="applicant-contact-item h-100">
                                    <dt>โทรศัพท์</dt>
                                    <dd class="mb-0">
                                        <?php if ($app['phone']): ?>
                                            <a class="link-primary" href="tel:<?= e($app['phone']) ?>"><?= e($app['phone']) ?></a>
                                        <?php else: ?>
                                            <span class="text-secondary">ยังไม่ได้ระบุ</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            </div>
                            <div class="col">
                                <div class="applicant-contact-item h-100">
                                    <dt>วันที่สมัคร</dt>
                                    <dd class="mb-0"><?= date('d/m/Y H:i', strtotime($app['created_at'])) ?></dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </section>

                <?php if ($skills): ?>
                    <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantSkillsHeading">
                        <div class="card-body p-4">
                            <p class="applicant-detail-eyebrow mb-1">SKILLS</p>
                            <h2 class="h4 mb-3" id="applicantSkillsHeading">ทักษะ</h2>
                            <ul class="list-unstyled d-flex flex-wrap gap-2 mb-0">
                                <?php foreach ($skills as $skill): ?>
                                    <li><span class="badge applicant-skill-badge"><?= e($skill) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($app['biography']): ?>
                    <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantBioHeading">
                        <div class="card-body p-4">
                            <p class="applicant-detail-eyebrow mb-1">ABOUT THE APPLICANT</p>
                            <h2 class="h4 mb-3" id="applicantBioHeading">แนะนำตัว</h2>
                            <p class="mb-0 text-secondary applicant-detail-copy"><?= nl2br(e($app['biography'])) ?></p>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($app['cover_note']): ?>
                    <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantNoteHeading">
                        <div class="card-body p-4">
                            <p class="applicant-detail-eyebrow mb-1">COVER NOTE</p>
                            <h2 class="h4 mb-3" id="applicantNoteHeading">จดหมายแนะนำตัว</h2>
                            <blockquote class="applicant-cover-note mb-0"><?= nl2br(e($app['cover_note'])) ?></blockquote>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($resumeFile || $app['portfolio_file_path'] || $app['portfolio_url']): ?>
                    <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantDocumentsHeading">
                        <div class="card-body p-4">
                            <p class="applicant-detail-eyebrow mb-1">DOCUMENTS & PORTFOLIO</p>
                            <h2 class="h4 mb-3" id="applicantDocumentsHeading">เอกสารและพอร์ตโฟลิโอ</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($resumeFile): ?>
                                    <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_resume&id=<?= $app['id'] ?>">เปิด Resume</a>
                                <?php endif; ?>
                                <?php if ($app['portfolio_file_path']): ?>
                                    <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_portfolio&id=<?= $app['id'] ?>">เปิด Portfolio</a>
                                <?php endif; ?>
                                <?php if ($app['portfolio_url']): ?>
                                    <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= e($app['portfolio_url']) ?>">Portfolio URL ↗</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <aside class="col-lg-4">
            <div class="vstack gap-4">
                <section class="card border-0 shadow-sm applicant-detail-card applicant-status-card" aria-labelledby="applicantStatusHeading">
                    <div class="card-body p-4">
                        <p class="applicant-detail-eyebrow mb-1">APPLICATION STATUS</p>
                        <h2 class="h4 mb-2" id="applicantStatusHeading">จัดการสถานะ</h2>
                        <p class="small text-secondary mb-4">เปลี่ยนผลการพิจารณา แล้วระบบจะแจ้งผู้สมัครให้ทราบ</p>

                        <?php if ($app['status'] === 'withdrawn'): ?>
                            <div class="alert alert-secondary mb-0">ผู้หางานถอนใบสมัครนี้แล้ว จึงไม่สามารถเปลี่ยนสถานะได้</div>
                        <?php else: ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                <fieldset class="border-0 p-0 m-0">
                                    <legend class="visually-hidden">เลือกสถานะผู้สมัคร</legend>
                                    <div class="vstack gap-2">
                                        <?php foreach ($statusOptions as $statusValue => $option): ?>
                                            <label class="applicant-status-option tone-<?= e($option['tone']) ?> <?= $app['status'] === $statusValue ? 'is-selected' : '' ?>">
                                                <input class="form-check-input m-0" type="radio" name="status" value="<?= e($statusValue) ?>" <?= $app['status'] === $statusValue ? 'checked' : '' ?>>
                                                <span class="applicant-status-option-icon" aria-hidden="true"><?= e($option['icon']) ?></span>
                                                <span class="applicant-status-option-copy">
                                                    <strong><?= e($option['label']) ?></strong>
                                                    <small><?= e($option['description']) ?></small>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                                <button class="btn btn-primary w-100 mt-3" type="submit">บันทึกสถานะ</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($app['status'] === 'completed'): ?>
                    <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantRatingHeading">
                        <div class="card-body p-4">
                            <p class="applicant-detail-eyebrow mb-1">WORK REVIEW</p>
                            <h2 class="h4 mb-3" id="applicantRatingHeading">ให้คะแนนหลังจบงาน</h2>
                            <?php
                            $ratingApplicationId = (int) $app['id'];
                            $ratingTargetName = $app['name'];
                            $ratingTargetRole = 'ผู้หางาน';
                            $ratingSummary = $workerRatingSummary;
                            $ratingAlreadySubmitted = $employerRatingSubmitted;
                            require APP_ROOT . '/partials/rating-form.php';
                            ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($app['status'] !== 'withdrawn'): ?>
                    <section class="card border-0 shadow-sm applicant-detail-card" aria-labelledby="applicantActionsHeading">
                        <div class="card-body p-4">
                            <p class="applicant-detail-eyebrow mb-1">QUICK ACTIONS</p>
                            <h2 class="h4 mb-3" id="applicantActionsHeading">ติดต่อผู้สมัคร</h2>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#applicantEmailModal" <?= mail_is_configured() ? '' : 'disabled' ?>>ส่งอีเมล</button>
                                <?php if ($app['phone']): ?>
                                    <a class="btn btn-outline-secondary" href="tel:<?= e($app['phone']) ?>">โทร <?= e($app['phone']) ?></a>
                                <?php endif; ?>
                            </div>
                            <?php if (!mail_is_configured()): ?>
                                <p class="small text-danger mb-0 mt-2">ระบบยังไม่พร้อมส่งอีเมล กรุณาติดต่อผู้ดูแลระบบ</p>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</main>

<?php if ($app['status'] !== 'withdrawn' && mail_is_configured()): ?>
    <div class="modal fade" id="applicantEmailModal" tabindex="-1" aria-labelledby="applicantEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="post">
                    <div class="modal-header">
                        <div>
                            <p class="applicant-detail-eyebrow mb-1">SEND EMAIL</p>
                            <h2 class="modal-title fs-5" id="applicantEmailModalLabel">ส่งอีเมลถึง <?= e($app['name']) ?></h2>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="send_email">
                        <div class="mb-3">
                            <label class="form-label" for="applicantEmailRecipient">ผู้รับ</label>
                            <input class="form-control" id="applicantEmailRecipient" type="email" value="<?= e($app['email']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="applicantEmailSubject">หัวข้อ</label>
                            <input class="form-control" id="applicantEmailSubject" name="email_subject" type="text" maxlength="160" value="<?= e('เรื่องใบสมัครงาน: ' . $job['job_title']) ?>" required>
                        </div>
                        <div>
                            <label class="form-label" for="applicantEmailMessage">ข้อความ</label>
                            <textarea class="form-control" id="applicantEmailMessage" name="email_message" rows="7" maxlength="5000" placeholder="เขียนข้อความถึงผู้สมัคร" required></textarea>
                            <div class="form-text">ผู้สมัครสามารถกด Reply เพื่อตอบกลับอีเมลของคุณได้โดยตรง</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light border" type="button" data-bs-dismiss="modal">ยกเลิก</button>
                        <button class="btn btn-primary" type="submit">ส่งอีเมล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/partials/footer.php'; ?>
