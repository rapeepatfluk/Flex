<?php
require_once __DIR__ . '/../config/config.php';
require_login('worker');

$applicationId = (int) ($_GET['id'] ?? 0);
$pdo = db();
$statement = $pdo->prepare("SELECT a.application_id AS id,a.application_status AS status,a.withdrawn_at,a.cover_note,a.resume_file_path AS application_resume,a.created_at,j.job_id,j.employer_user_id,j.job_title AS title,j.work_location AS location,j.work_schedule AS schedule,j.pay_amount,j.pay_unit,j.job_description AS description,ep.company_name,ep.company_description,ep.company_logo_path AS company_logo,u_emp.phone AS employer_phone,u_emp.email AS employer_email FROM applications a JOIN jobs j ON j.job_id=a.job_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN users u_emp ON u_emp.user_id=j.employer_user_id WHERE a.application_id=? AND a.worker_user_id=?");
$statement->execute([$applicationId, user()['id']]);
$application = $statement->fetch();
if (!$application) redirect('worker/dashboard.php');

$employerEmailComposeUrl = null;
if ($application['employer_email'] && filter_var($application['employer_email'], FILTER_VALIDATE_EMAIL)) {
    $emailSubject = rawurlencode('สอบถามเกี่ยวกับงาน: ' . $application['title']);
    $emailBody = rawurlencode("สวัสดีครับ/ค่ะ\r\n\r\nขอติดต่อเกี่ยวกับตำแหน่ง " . $application['title'] . "\r\n\r\n");
    $employerEmailComposeUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to='
        . rawurlencode($application['employer_email'])
        . '&su=' . $emailSubject
        . '&body=' . $emailBody;
}

$ratingSummaryStatement = $pdo->prepare('SELECT ROUND(AVG(rating_by_worker), 1) AS average, COUNT(rating_by_worker) AS count FROM applications WHERE rating_by_worker IS NOT NULL AND job_id IN (SELECT job_id FROM jobs WHERE employer_user_id=?)');
$ratingSummaryStatement->execute([$application['employer_user_id']]);
$employerRatingSummary = $ratingSummaryStatement->fetch() ?: ['average' => null, 'count' => 0];
$ratingSubmittedStatement = $pdo->prepare('SELECT rating_by_worker FROM applications WHERE application_id=?');
$ratingSubmittedStatement->execute([$applicationId]);
$workerRatingSubmitted = $ratingSubmittedStatement->fetchColumn() !== null;

$statusLabels = [
    'submitted' => 'รอพิจารณา',
    'eligible' => 'มีสิทธิ์สัมภาษณ์',
    'interview_passed' => 'ผ่านสัมภาษณ์แล้ว',
    'completed' => 'งานเสร็จสิ้น',
    'not_selected' => 'ไม่ผ่าน',
    'withdrawn' => 'ถอนใบสมัครแล้ว',
];
$pageTitle = 'รายละเอียดการสมัคร | FLEXJOB';
$pageStyles = ['worker-application-detail', 'rating'];
require APP_ROOT . '/partials/header.php';
?>
<main class="application-detail-page py-4 py-lg-5">
    <div class="container">
        <a class="btn btn-link px-0 mb-4 text-decoration-none" href="<?= BASE_URL ?>/worker/dashboard.php">← กลับไปงานที่สมัคร</a>

        <section class="application-detail-hero card border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-4">
                    <div class="application-employer-logo flex-shrink-0">
                        <?php if ($application['company_logo']): ?><img src="<?= BASE_URL . '/' . e($application['company_logo']) ?>" alt="โลโก้ <?= e($application['company_name']) ?>" decoding="async"><?php else: ?><?= e(mb_substr($application['company_name'], 0, 1)) ?><?php endif ?>
                    </div>
                    <div class="flex-grow-1"><p class="eyebrow mb-2">APPLICATION DETAIL</p><h1 class="h2 mb-2"><?= e($application['title']) ?></h1><p class="mb-0"><?= e($application['company_name']) ?> <span aria-hidden="true">·</span> <?= pay_text($application) ?></p></div>
                    <div class="align-self-lg-start"><span class="application-detail-status <?= e($application['status']) ?>"><?= e($statusLabels[$application['status']] ?? $application['status']) ?></span></div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-4">
                <section class="card border-0 shadow-sm rounded-4 application-status-card" aria-labelledby="application-status-title">
                    <div class="card-body p-4"><p class="eyebrow mb-2">APPLICATION STATUS</p><h2 class="h4 mb-4" id="application-status-title">สถานะการสมัคร</h2>
                        <div class="application-timeline">
                            <div class="timeline-item is-complete"><span class="timeline-dot" aria-hidden="true"></span><div><b>ส่งใบสมัครแล้ว</b><small><?= date('d/m/Y H:i', strtotime($application['created_at'])) ?></small></div></div>
                            <?php if ($application['status'] === 'withdrawn'): ?>
                                <div class="timeline-item is-complete is-muted"><span class="timeline-dot" aria-hidden="true"></span><div><b>ถอนใบสมัครแล้ว</b><small><?= $application['withdrawn_at'] ? date('d/m/Y H:i', strtotime($application['withdrawn_at'])) : '' ?></small></div></div>
                            <?php else: ?>
                                <div class="timeline-item <?= in_array($application['status'], ['eligible', 'interview_passed', 'completed', 'not_selected'], true) ? 'is-complete' : 'is-current' ?>"><span class="timeline-dot" aria-hidden="true"></span><div><b>อยู่ระหว่างพิจารณา</b><small>ผู้ว่าจ้างกำลังตรวจสอบโปรไฟล์ของคุณ</small></div></div>
                                <?php if (in_array($application['status'], ['eligible', 'interview_passed', 'completed'], true)): ?><div class="timeline-item is-complete is-positive"><span class="timeline-dot" aria-hidden="true"></span><div><b>มีสิทธิ์สัมภาษณ์</b><small>ผู้ว่าจ้างพร้อมติดต่อคุณ</small></div></div><?php endif ?>
                                <?php if (in_array($application['status'], ['interview_passed', 'completed'], true)): ?><div class="timeline-item is-complete is-positive"><span class="timeline-dot" aria-hidden="true"></span><div><b>ผ่านสัมภาษณ์แล้ว</b><small>ผู้ว่าจ้างยืนยันว่าคุณผ่านการสัมภาษณ์</small></div></div><?php endif ?>
                                <?php if ($application['status'] === 'completed'): ?><div class="timeline-item is-complete is-positive"><span class="timeline-dot" aria-hidden="true"></span><div><b>งานเสร็จสิ้น</b><small>คุณสามารถให้คะแนนผู้ว่าจ้างได้</small></div></div><?php endif ?>
                                <?php if ($application['status'] === 'not_selected'): ?><div class="timeline-item is-complete is-negative"><span class="timeline-dot" aria-hidden="true"></span><div><b>ไม่ผ่านการคัดเลือก</b><small>คุณยังสามารถสมัครงานอื่นได้</small></div></div><?php endif ?>
                            <?php endif ?>
                        </div>

                        <?php if ($application['status'] === 'submitted'): ?>
                            <form class="border-top mt-4 pt-4" method="post" action="<?= BASE_URL ?>/worker/withdraw-application.php" onsubmit="return confirm('ยืนยันการถอนใบสมัครนี้? หากงานยังเปิดรับ คุณสามารถสมัครใหม่ได้ภายหลัง')">
                                <?= csrf_field() ?><input type="hidden" name="application_id" value="<?= $application['id'] ?>"><button class="btn btn-outline-danger w-100" type="submit">ถอนใบสมัคร</button><p class="form-text mb-0 mt-2">ถอนได้ก่อนผู้ว่าจ้างเปลี่ยนผลการพิจารณา</p>
                            </form>
                        <?php endif ?>
                    </div>
                </section>

                <?php if (in_array($application['status'], ['eligible', 'interview_passed', 'completed'], true)): ?>
                    <section class="card border-0 shadow-sm rounded-4 contact-card mt-4"><div class="card-body p-4"><p class="eyebrow mb-2">CONTACT</p><h2 class="h5 mb-3">ติดต่อผู้ว่าจ้าง</h2><p class="text-secondary small">ผู้ว่าจ้างเปิดข้อมูลติดต่อให้คุณแล้ว</p><?php if ($employerEmailComposeUrl): ?><a class="contact-link" href="<?= e($employerEmailComposeUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="เปิด Gmail ในแท็บใหม่เพื่อติดต่อ <?= e($application['company_name']) ?>">✉ <?= e($application['employer_email']) ?> <span aria-hidden="true">↗</span></a><?php endif ?><?php if ($application['employer_phone']): ?><a class="contact-link" href="tel:<?= e($application['employer_phone']) ?>">⌕ <?= e($application['employer_phone']) ?></a><?php endif ?></div></section>
                <?php endif ?>

                <?php if ($application['application_resume']): ?>
                    <section class="card border-0 shadow-sm rounded-4 application-document-card mt-4"><div class="card-body p-4"><p class="eyebrow mb-2">DOCUMENT</p><h2 class="h5">เอกสารที่แนบ</h2><p class="text-secondary small">Resume ที่ใช้สมัครงานนี้</p><a class="btn btn-primary w-100" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_resume&id=<?= $application['id'] ?>">เปิดดู Resume</a></div></section>
                <?php endif ?>
            </div>

            <div class="col-lg-8">
                <section class="card border-0 shadow-sm rounded-4 application-job-card" aria-labelledby="job-information-title">
                    <div class="card-body p-4 p-lg-5"><p class="eyebrow mb-2">JOB INFORMATION</p><h2 class="h4 mb-4" id="job-information-title">รายละเอียดงาน</h2>
                        <div class="row row-cols-1 row-cols-md-3 g-0 application-facts mb-4"><div class="col"><div><span>ค่าตอบแทน</span><strong><?= pay_text($application) ?></strong></div></div><div class="col"><div><span>สถานที่ทำงาน</span><strong><?= e($application['location'] ?: 'ไม่ระบุ') ?></strong></div></div><div class="col"><div><span>วัน / เวลาทำงาน</span><strong><?= e($application['schedule'] ?: 'ไม่ระบุ') ?></strong></div></div></div>
                        <?php if ($application['description']): ?><h3 class="h6 mb-2">รายละเอียดงาน</h3><div class="application-description mb-4"><?= nl2br(e($application['description'])) ?></div><?php endif ?>
                        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/job.php?id=<?= $application['job_id'] ?>">ดูประกาศงานเต็ม <span aria-hidden="true">→</span></a>
                    </div>
                </section>

                <?php if ($application['cover_note']): ?>
                    <section class="card border-0 shadow-sm rounded-4 application-note-card mt-4"><div class="card-body p-4 p-lg-5"><p class="eyebrow mb-2">YOUR MESSAGE</p><h2 class="h5">ข้อความที่ส่งถึงผู้ว่าจ้าง</h2><blockquote class="mb-0 mt-3">“<?= nl2br(e($application['cover_note'])) ?>”</blockquote></div></section>
                <?php endif ?>

                <section class="card border-0 shadow-sm rounded-4 application-company-card mt-4"><div class="card-body p-4 p-lg-5"><p class="eyebrow mb-2">ABOUT EMPLOYER</p><div class="d-flex align-items-center gap-3"><div class="application-employer-logo application-employer-logo-sm flex-shrink-0"><?php if ($application['company_logo']): ?><img src="<?= BASE_URL . '/' . e($application['company_logo']) ?>" alt="โลโก้ <?= e($application['company_name']) ?>" loading="lazy" decoding="async"><?php else: ?><?= e(mb_substr($application['company_name'], 0, 1)) ?><?php endif ?></div><h2 class="h5 mb-0"><?= e($application['company_name']) ?></h2></div><div class="mt-3"><?php $ratingSummary = $employerRatingSummary; require APP_ROOT . '/partials/rating-summary.php'; ?></div><?php if ($application['company_description']): ?><p class="application-company-description mb-0 mt-3"><?= nl2br(e($application['company_description'])) ?></p><?php endif ?></div></section>

                <?php if ($application['status'] === 'completed'): ?>
                <section class="card border-0 shadow-sm rounded-4 application-rating-card mt-4"><div class="card-body p-4 p-lg-5"><p class="eyebrow mb-2">WORK REVIEW</p><h2 class="h5">ให้คะแนนผู้ว่าจ้าง</h2><?php $ratingApplicationId = (int) $application['id']; $ratingTargetName = $application['company_name']; $ratingTargetRole = 'ผู้ว่าจ้าง'; $ratingSummary = $employerRatingSummary; $ratingAlreadySubmitted = $workerRatingSubmitted; require APP_ROOT . '/partials/rating-form.php'; ?></div></section>
                <?php endif ?>
            </div>
        </div>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
