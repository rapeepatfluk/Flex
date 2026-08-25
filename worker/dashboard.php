<?php require_once __DIR__ . '/../config/config.php';
require_login('worker');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $resume = upload_file('resume', ['pdf', 'doc', 'docx'], 'resumes');
        $pdo->prepare('UPDATE worker_profiles SET professional_headline=?,skills=?,biography=?,resume_file_path=COALESCE(?,resume_file_path) WHERE user_id=?')->execute([trim($_POST['headline']), trim($_POST['skills']), trim($_POST['bio']), $resume, user()['id']]);
        flash('success', 'บันทึกโปรไฟล์แล้ว');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('worker/dashboard.php');
}
$s = $pdo->prepare("SELECT wp.professional_headline AS headline,wp.skills,wp.biography AS bio,wp.resume_file_path AS resume_file,CONCAT(u.first_name,' ',u.last_name) AS name,u.email,u.phone FROM worker_profiles wp JOIN users u ON u.user_id=wp.user_id WHERE wp.user_id=?");
$s->execute([user()['id']]);
$profile = $s->fetch();
$s = $pdo->prepare("SELECT a.application_id,a.application_status AS status,a.created_at,j.job_title AS title,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,ep.company_name FROM applications a JOIN jobs j ON j.job_id=a.job_id JOIN employer_profiles ep ON ep.user_id=j.employer_user_id WHERE a.worker_user_id=? ORDER BY a.created_at DESC");
$s->execute([user()['id']]);
$apps = $s->fetchAll();
$pageTitle = 'พื้นที่ของฉัน | FLEXJOB';
require APP_ROOT . '/partials/header.php'; ?>
<main class="dashboard">
    <div class="dashboard-title">
        <div>
            <p class="eyebrow">WORKER DASHBOARD</p>
            <h1>สวัสดี, <?= e(user()['name']) ?></h1>
            <p>รายละเอียดงานที่สมัคร</p>
        </div><a class="btn btn-primary" href="<?= BASE_URL ?>/jobs.php">ค้นหางาน</a>
    </div>
    <div class="dashboard-grid">
        <section class="panel" id="applications">
            <h2>งานที่สมัคร <span class="count"><?= count($apps) ?></span></h2><?php foreach ($apps as $app): ?><a class="application application-link" href="<?= BASE_URL ?>/worker/application-detail.php?id=<?= $app['application_id'] ?>">
                    <div><b><?= e($app['title']) ?></b>
                        <p><?= e($app['company_name']) ?> · <?= pay_text($app) ?></p><small>สมัครเมื่อ <?= date('d/m/Y', strtotime($app['created_at'])) ?></small>
                    </div><span class="status <?= $app['status'] ?>"><?= ['submitted' => 'รอพิจารณา', 'eligible' => 'มีสิทธิ์สัมภาษณ์', 'not_selected' => 'ไม่ผ่าน'][$app['status']] ?></span>
                </a><?php endforeach ?><?php if (!$apps): ?><div class="empty">ยังไม่มีงานที่สมัคร <a href="<?= BASE_URL ?>/jobs.php">เริ่มค้นหางาน</a></div><?php endif ?>
        </section>
    </div>
</main><?php require APP_ROOT . '/partials/footer.php'; ?>
