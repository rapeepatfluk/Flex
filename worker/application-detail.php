<?php require_once __DIR__ . '/../config/config.php';
require_login('worker');
$appId = (int)($_GET['id'] ?? 0);
$pdo = db();

$s = $pdo->prepare("
    SELECT
        a.application_id AS id,
        a.application_status AS status,
        a.withdrawn_at,
        a.cover_note,
        a.resume_file_path AS application_resume,
        a.created_at,
        j.job_id,
        j.job_title AS title,
        j.work_location AS location,
        j.work_schedule AS schedule,
        j.pay_amount,
        j.pay_unit,
        j.job_description AS description,
        ep.company_name,
        ep.company_description,
        ep.company_logo_path AS company_logo,
        u_emp.phone AS employer_phone,
        u_emp.email AS employer_email
    FROM applications a
    JOIN jobs j ON j.job_id = a.job_id
    JOIN employer_profiles ep ON ep.user_id = j.employer_user_id
    JOIN users u_emp ON u_emp.user_id = j.employer_user_id
    WHERE a.application_id = ? AND a.worker_user_id = ?
");
$s->execute([$appId, user()['id']]);
$app = $s->fetch();
if (!$app) redirect('worker/dashboard.php');

$statusLabel = ['submitted' => 'รอพิจารณา', 'eligible' => 'มีสิทธิ์สัมภาษณ์', 'not_selected' => 'ไม่ผ่าน', 'withdrawn' => 'ถอนใบสมัครแล้ว'];
$pageTitle = 'รายละเอียดการสมัคร | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="detail">
    <a class="back-link" href="<?= BASE_URL ?>/worker/dashboard.php">← กลับแดชบอร์ด</a>

    <div class="app-detail-header">
        <div class="app-detail-hero">
            <?php if ($app['company_logo']): ?>
                <img class="company-logo company-logo-detail" src="<?= BASE_URL . '/' . e($app['company_logo']) ?>" alt="<?= e($app['company_name']) ?>">
            <?php else: ?>
                <div class="company-logo-placeholder"><?= e(mb_substr($app['company_name'], 0, 1)) ?></div>
            <?php endif; ?>
            <div>
                <p class="eyebrow">รายละเอียดการสมัคร</p>
                <h1><?= e($app['title']) ?></h1>
                <p class="company-line"><?= e($app['company_name']) ?> · <?= pay_text($app) ?></p>
            </div>
        </div>
        <span class="status <?= $app['status'] ?> status-lg"><?= $statusLabel[$app['status']] ?? $app['status'] ?></span>
    </div>

    <div class="app-detail-grid">

        <!-- Left: Timeline & Application Info -->
        <div class="app-detail-left">
            <!-- Status Timeline -->
            <div class="panel app-timeline-panel">
                <h2>สถานะการสมัคร</h2>
                <div class="app-timeline">
                    <div class="timeline-step <?= in_array($app['status'], ['submitted','eligible','not_selected','withdrawn']) ? 'done' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <b>ส่งใบสมัครแล้ว</b>
                            <small><?= date('d/m/Y H:i', strtotime($app['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php if ($app['status'] !== 'withdrawn'): ?><div class="timeline-step <?= $app['status'] === 'eligible' || $app['status'] === 'not_selected' ? 'done' : '' ?> <?= $app['status'] === 'eligible' ? 'current' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <b>อยู่ระหว่างพิจารณา</b>
                            <small>ผู้ว่าจ้างกำลังตรวจสอบโปรไฟล์ของคุณ</small>
                        </div>
                    </div><?php endif ?>
                    <?php if ($app['status'] === 'eligible'): ?>
                    <div class="timeline-step current eligible-step">
                        <div class="timeline-dot eligible-dot"></div>
                        <div class="timeline-content">
                            <b class="eligible-text">🎉 มีสิทธิ์สัมภาษณ์!</b>
                            <small>ผู้ว่าจ้างจะติดต่อกลับผ่านอีเมลหรือโทรศัพท์ที่ลงทะเบียนไว้</small>
                        </div>
                    </div>
                    <?php elseif ($app['status'] === 'not_selected'): ?>
                    <div class="timeline-step done not-selected-step">
                        <div class="timeline-dot not-selected-dot"></div>
                        <div class="timeline-content">
                            <b class="not-selected-text">ไม่ผ่านการคัดเลือก</b>
                            <small>ขอบคุณที่สนใจสมัครงาน สามารถสมัครงานอื่นได้</small>
                        </div>
                    </div>
                    <?php elseif ($app['status'] === 'withdrawn'): ?>
                    <div class="timeline-step done">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <b>ถอนใบสมัครแล้ว</b>
                            <small><?= $app['withdrawn_at'] ? date('d/m/Y H:i', strtotime($app['withdrawn_at'])) : '' ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($app['status'] === 'submitted'): ?>
                <form class="mt-16" method="post" action="<?= BASE_URL ?>/worker/withdraw-application.php" onsubmit="return confirm('ยืนยันการถอนใบสมัครนี้? หลังถอนแล้วจะสมัครงานเดิมซ้ำไม่ได้')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                    <button class="btn btn-outline-danger" type="submit">ถอนใบสมัคร</button>
                    <p class="muted" style="font-size:12px;margin:8px 0 0">ถอนได้เฉพาะช่วงที่ผู้ว่าจ้างยังไม่ได้เปลี่ยนผลการพิจารณา</p>
                </form>
                <?php endif; ?>

                <?php if ($app['status'] === 'eligible'): ?>
                <div class="contact-callout">
                    <p class="eyebrow" style="margin-bottom:6px">ช่องทางติดต่อผู้ว่าจ้าง</p>
                    <?php if ($app['employer_email']): ?>
                    <a href="mailto:<?= e($app['employer_email']) ?>" class="contact-item">
                        <span>📧</span> <?= e($app['employer_email']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($app['employer_phone']): ?>
                    <a href="tel:<?= e($app['employer_phone']) ?>" class="contact-item">
                        <span>📞</span> <?= e($app['employer_phone']) ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cover Note -->
            <?php if ($app['cover_note']): ?>
            <div class="panel mt-16">
                <h2>จดหมายแนะนำตัว</h2>
                <blockquote class="cover-note-quote"><?= nl2br(e($app['cover_note'])) ?></blockquote>
            </div>
            <?php endif; ?>

            <!-- Resume -->
            <?php if ($app['application_resume']): ?>
            <div class="panel mt-16">
                <h2>เอกสารที่แนบ</h2>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=application_resume&id=<?= $app['id'] ?>">
                    📄 เปิดดู Resume ที่แนบ
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Job Details -->
        <div class="app-detail-right">
            <div class="panel">
                <h2>รายละเอียดงาน</h2>
                <div class="detail-stats">
                    <div>
                        <small>ค่าตอบแทน</small>
                        <b><?= pay_text($app) ?></b>
                    </div>
                    <div>
                        <small>สถานที่ทำงาน</small>
                        <b><?= e($app['location'] ?: '-') ?></b>
                    </div>
                    <div>
                        <small>วันทำงาน</small>
                        <b><?= e($app['schedule'] ?: '-') ?></b>
                    </div>
                </div>

                <?php if ($app['description']): ?>
                <h3 style="font-size:15px;margin-top:20px;">รายละเอียดงาน</h3>
                <div class="job-description"><?= nl2br(e($app['description'])) ?></div>
                <?php endif; ?>

                <a class="btn btn-outline mt-16" href="<?= BASE_URL ?>/job.php?id=<?= $app['job_id'] ?>">ดูประกาศงานเต็ม →</a>
            </div>

            <!-- Company Info -->
            <div class="panel mt-16">
                <h2>เกี่ยวกับบริษัท</h2>
                <div class="company-info-row">
                    <?php if ($app['company_logo']): ?>
                    <img class="company-logo company-logo-detail" src="<?= BASE_URL . '/' . e($app['company_logo']) ?>" alt="">
                    <?php else: ?>
                    <div class="company-logo-placeholder sm"><?= e(mb_substr($app['company_name'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div>
                        <b><?= e($app['company_name']) ?></b>
                    </div>
                </div>
                <?php if ($app['company_description']): ?>
                <p class="muted" style="margin-top:12px;font-size:13px;line-height:1.7"><?= nl2br(e($app['company_description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require APP_ROOT . '/partials/footer.php'; ?>
