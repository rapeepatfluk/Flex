<?php
require_once __DIR__ . '/../config/config.php';

require_login('employer');

$appId = (int) ($_GET['id'] ?? 0);
$jobId = (int) ($_GET['job'] ?? 0);
$pdo = db();

$jobStatement = $pdo->prepare('SELECT j.job_id,j.job_title,ep.company_name FROM jobs j LEFT JOIN employer_profiles ep ON ep.user_id=j.employer_user_id WHERE j.job_id=? AND j.employer_user_id=?');
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

$applicantEmailComposeUrl = null;
if ($app['email'] && filter_var($app['email'], FILTER_VALIDATE_EMAIL)) {
    $companyName = trim((string) ($job['company_name'] ?? ''));
    $employerName = trim((string) user()['name']);
    $signatureLines = array_values(array_filter([$employerName, $companyName], static fn (string $line): bool => $line !== ''));

    if ($app['status'] === 'eligible') {
        $emailSubjectText = 'นัดสัมภาษณ์งาน ตำแหน่ง: ' . $job['job_title'];
        $emailBodyText = implode("\r\n", [
            'เรียน คุณ ' . $app['name'],
            '',
            'ทาง' . ($companyName !== '' ? 'บริษัท ' . $companyName : 'ผู้ว่าจ้าง') . ' มีความยินดีขอเชิญคุณเข้าร่วมการสัมภาษณ์สำหรับตำแหน่ง ' . $job['job_title'] . ' โดยมีรายละเอียดดังนี้',
            '',
            'วันที่: ',
            'เวลา: ',
            'รูปแบบการสัมภาษณ์: ออนไลน์ผ่าน Google Meet / Zoom',
            'ลิงก์เข้าร่วมสัมภาษณ์: ',
            '',
            'กรุณาตอบกลับอีเมลนี้เพื่อยืนยันการเข้าร่วม หากวันหรือเวลาดังกล่าวไม่สะดวก สามารถแจ้งช่วงเวลาที่สะดวกกลับมาได้',
            '',
            'ขอบคุณครับ/ค่ะ',
            ...$signatureLines,
        ]);
    } else {
        $emailSubjectText = 'เรื่องใบสมัครงาน: ' . $job['job_title'];
        $emailBodyText = implode("\r\n", [
            'สวัสดีครับ/ค่ะ คุณ ' . $app['name'],
            '',
            'ขอติดต่อเกี่ยวกับใบสมัครตำแหน่ง ' . $job['job_title'],
            '',
            'ขอบคุณครับ/ค่ะ',
            ...$signatureLines,
        ]);
    }

    $applicantEmailComposeUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to='
        . rawurlencode($app['email'])
        . '&su=' . rawurlencode($emailSubjectText)
        . '&body=' . rawurlencode($emailBodyText);
}

$statusOptions = [
    'submitted' => ['label' => 'รอพิจารณา', 'description' => 'ยังไม่ได้ตัดสินใจ', 'tone' => 'warning', 'icon' => '◷'],
    'eligible' => ['label' => 'มีสิทธิ์สัมภาษณ์', 'description' => 'ผ่านการคัดเลือกเบื้องต้น', 'tone' => 'primary', 'icon' => '✓'],
    'not_selected' => ['label' => 'ไม่ผ่านการคัดเลือก', 'description' => 'ผู้สมัครไม่ผ่านเกณฑ์', 'tone' => 'danger', 'icon' => '×'],
    'interview_passed' => ['label' => 'ผ่านสัมภาษณ์แล้ว', 'description' => 'พร้อมเริ่มต้นขั้นตอนการทำงาน', 'tone' => 'success', 'icon' => '✦'],
    'completed' => ['label' => 'งานเสร็จสิ้น', 'description' => 'เปิดให้ทั้งสองฝ่ายให้คะแนนกันได้', 'tone' => 'info', 'icon' => '★'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($statusOptions[(string) ($_POST['status'] ?? '')])) {
    try {
        verify_csrf();
        $requestedStatus = (string) $_POST['status'];
        $rating = null;
        if ($requestedStatus === 'completed' && array_key_exists('rating', $_POST)) {
            $rating = filter_var(
                $_POST['rating'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 5]]
            );
            if ($rating === false) {
                throw new RuntimeException('กรุณาเลือกคะแนน 1–5 ดาว');
            }
        }

        $pdo->beginTransaction();
        $statusChanged = application_update_status_by_employer(
            $pdo,
            (int) user()['id'],
            $jobId,
            $appId,
            $requestedStatus
        );

        $ratingSaved = false;
        if ($rating !== null) {
            $ratingUpdate = $pdo->prepare('UPDATE applications SET rating_by_employer=?, rated_by_employer_at=NOW() WHERE application_id=? AND rating_by_employer IS NULL');
            $ratingUpdate->execute([$rating, $appId]);
            if (!$ratingUpdate->rowCount()) {
                throw new RuntimeException('คุณให้คะแนนสำหรับงานนี้ไปแล้ว');
            }
            notification_create(
                $pdo,
                (int) $app['worker_user_id'],
                'ได้รับคะแนนใหม่',
                user()['name'] . ' ให้คะแนนคุณ ' . $rating . ' ดาว หลังจบงาน',
                'worker/application-detail.php?id=' . $appId
            );
            $ratingSaved = true;
        }
        $pdo->commit();

        if ($statusChanged) {
            notify_worker_status($appId);
        }

        if ($ratingSaved) {
            flash('success', 'บันทึกสถานะงานเสร็จสิ้นและคะแนน ' . $rating . ' ดาวเรียบร้อยแล้ว');
        } elseif ($statusChanged) {
            flash('success', 'อัปเดตสถานะผู้สมัครแล้ว');
        } else {
            flash('success', 'สถานะผู้สมัครไม่มีการเปลี่ยนแปลง');
        }
        if ($requestedStatus === 'eligible') {
            flash('interview_email_prompt', '1');
        }
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Application status update failed: ' . $e->getMessage());
        flash('error', 'ไม่สามารถอัปเดตสถานะผู้สมัครได้ กรุณาลองใหม่อีกครั้ง');
    }
    redirect('employer/applicant-detail.php?id=' . $appId . '&job=' . $jobId);
}

$showInterviewEmailPrompt = flash('interview_email_prompt') === '1';

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
$pageScripts = ['employer-applicant-detail'];
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

    <?php if ($showInterviewEmailPrompt && $applicantEmailComposeUrl): ?>
        <div class="alert alert-primary border-0 shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4" role="status">
            <div>
                <strong class="d-block mb-1">ผู้สมัครอยู่ในสถานะ “มีสิทธิ์สัมภาษณ์” แล้ว</strong>
                <span>ขั้นตอนถัดไป กรุณาส่งอีเมลนัดสัมภาษณ์ พร้อมระบุวัน เวลา และลิงก์ Google Meet หรือ Zoom</span>
            </div>
            <a class="btn btn-primary flex-shrink-0" href="<?= e($applicantEmailComposeUrl) ?>" target="_blank" rel="noopener noreferrer">ส่งอีเมลนัดสัมภาษณ์ <span aria-hidden="true">↗</span></a>
        </div>
    <?php endif; ?>

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
                            <h2 class="h4 mb-3" id="applicantSkillsHeading">ความสามารถ</h2>
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
                            <form id="applicantStatusForm" method="post">
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
                                <?php if ($applicantEmailComposeUrl): ?>
                                    <a class="btn btn-outline-primary" href="<?= e($applicantEmailComposeUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="เปิด Gmail ในแท็บใหม่เพื่อส่งอีเมลถึง <?= e($app['name']) ?>"><?= $app['status'] === 'eligible' ? 'ส่งอีเมลนัดสัมภาษณ์' : 'ส่งอีเมล' ?> <span aria-hidden="true">↗</span></a>
                                <?php endif; ?>
                                <?php if ($app['phone']): ?>
                                    <a class="btn btn-outline-secondary" href="tel:<?= e($app['phone']) ?>">โทร <?= e($app['phone']) ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</main>

<?php if ($app['status'] !== 'withdrawn' && !$employerRatingSubmitted): ?>
    <div class="modal fade applicant-rating-modal" id="completedRatingModal" tabindex="-1" aria-labelledby="completedRatingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form id="completedRatingForm" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                    <input type="hidden" name="status" value="completed">
                    <div class="modal-header">
                        <div>
                            <p class="applicant-detail-eyebrow mb-1">WORK REVIEW</p>
                            <h2 class="modal-title fs-5" id="completedRatingModalLabel">ให้คะแนนหลังจบงาน</h2>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary mb-4">งานของ <?= e($app['name']) ?> เสร็จสิ้นแล้ว เลือกคะแนนเพื่อบันทึกพร้อมสถานะงาน</p>
                        <fieldset class="border-0 p-0 m-0">
                            <legend class="h6 mb-2">คะแนนสำหรับผู้หางาน</legend>
                            <div class="rating-stars" aria-describedby="completedRatingHint">
                                <?php for ($score = 5; $score >= 1; $score--): ?>
                                    <input class="rating-input" id="completed-rating-<?= $app['id'] ?>-<?= $score ?>" type="radio" name="rating" value="<?= $score ?>" required>
                                    <label class="rating-star" for="completed-rating-<?= $app['id'] ?>-<?= $score ?>"><span class="visually-hidden"><?= $score ?> ดาว</span><span aria-hidden="true">★</span></label>
                                <?php endfor; ?>
                            </div>
                            <p class="form-text mb-0" id="completedRatingHint">เลือกจำนวนดาวจาก 1 ถึง 5 ดาว</p>
                        </fieldset>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light border" type="button" data-bs-dismiss="modal">กลับไปเลือกสถานะ</button>
                        <button class="btn btn-primary" type="submit">บันทึกงานเสร็จสิ้นและคะแนน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/partials/footer.php'; ?>
