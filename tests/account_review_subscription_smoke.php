<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (validate_username('Test.User_01') !== 'test.user_01') throw new RuntimeException('Username normalization failed');
foreach (['abc','_invalid','invalid-username','admin'] as $invalid) {
    try {
        validate_username($invalid);
        throw new RuntimeException('Invalid username was accepted: ' . $invalid);
    } catch (RuntimeException $exception) {
        if (str_starts_with($exception->getMessage(), 'Invalid username was accepted')) throw $exception;
    }
}

$pdo = db();
$columns = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='username'")->fetchColumn();
if ($columns !== 'username') throw new RuntimeException('Username database column is missing');
if ((int) $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn() < 1) throw new RuntimeException('Legacy ratings were not migrated to reviews');

$pdo->beginTransaction();
try {
    $password = 'CodexLoginTest!2026';
    $pdo->prepare("INSERT INTO users (username,first_name,last_name,email,password_hash,role,account_status)
        VALUES ('codex.login.test','Codex','Login Test','codex-login-test@flexjob.local',?,'worker','active')")
        ->execute([password_hash($password, PASSWORD_DEFAULT)]);
    $login = $pdo->prepare('SELECT username,password_hash FROM users WHERE email=? OR username=? LIMIT 1');
    $login->execute(['codex.login.test','codex.login.test']);
    $loginUser = $login->fetch();
    if (!$loginUser || !password_verify($password,$loginUser['password_hash'])) {
        throw new RuntimeException('Username login lookup failed');
    }
} finally {
    $pdo->rollBack();
}

$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO users (username,first_name,last_name,email,password_hash,role,account_status,email_verified_at)
        VALUES (?,?,? ,?,?, 'employer','active',NOW())")
        ->execute(['quota.' . $suffix, 'Quota', 'Fixture', 'quota-' . $suffix . '@flexjob.local', password_hash('QuotaFixture!2026', PASSWORD_DEFAULT)]);
    $employerId = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO employer_profiles (user_id,company_name) VALUES (?,'Quota Fixture Co.')")->execute([$employerId]);
    $categoryId = (int) $pdo->query('SELECT job_category_id FROM job_categories ORDER BY job_category_id LIMIT 1')->fetchColumn();
    if (!$categoryId) throw new RuntimeException('Quota smoke test requires a job category');
    $insertJob = $pdo->prepare("INSERT INTO jobs
        (employer_user_id,job_category_id,job_title,job_description,work_location,work_province,application_deadline,pay_amount,pay_unit,open_positions,job_status)
        VALUES (?,?,?,'Quota fixture','บุรีรัมย์',?,DATE_ADD(CURDATE(),INTERVAL 30 DAY),500,'day',1,'published')");
    for ($index = 1; $index <= FREE_ACTIVE_JOB_LIMIT + 1; $index++) {
        $insertJob->execute([$employerId, $categoryId, 'Quota fixture ' . $index, FLEXJOB_PROVINCE]);
    }

    subscription_sync_statuses($pdo);
    $countStatement = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE employer_user_id=? AND job_status='published'");
    $countStatement->execute([$employerId]);
    if ((int) $countStatement->fetchColumn() !== FREE_ACTIVE_JOB_LIMIT) {
        throw new RuntimeException('Free limit was not enforced for an employer who never subscribed');
    }
} finally {
    $pdo->rollBack();
}

$application = $pdo->query("SELECT a.application_id,a.worker_user_id,j.employer_user_id
    FROM applications a JOIN jobs j ON j.job_id=a.job_id
    WHERE a.application_status='completed' ORDER BY a.application_id LIMIT 1")->fetch();
if ($application) {
    $pdo->beginTransaction();
    try {
        $applicationId = (int) $application['application_id'];
        $reviewerId = (int) $application['worker_user_id'];
        $pdo->prepare('DELETE FROM reviews WHERE application_id=? AND reviewer_user_id=?')->execute([$applicationId,$reviewerId]);
        $pdo->prepare('UPDATE applications SET rating_by_worker=NULL,rated_by_worker_at=NULL WHERE application_id=?')->execute([$applicationId]);
        try {
            review_create_for_application($pdo,$applicationId,$reviewerId,5,'');
            throw new RuntimeException('Empty review comment was accepted');
        } catch (RuntimeException $exception) {
            if (!str_contains($exception->getMessage(), 'ความคิดเห็น')) throw $exception;
        }
        $created = review_create_for_application($pdo,$applicationId,$reviewerId,5,'ทำงานร่วมกันดีและชำระเงินตรงเวลา');
        if ($created['reviewee_user_id'] !== (int) $application['employer_user_id']) throw new RuntimeException('Review recipient is incorrect');
        $submitted = review_submitted_for_application($pdo,$applicationId,$reviewerId);
        if (!$submitted || (int) $submitted['rating'] !== 5 || trim((string) $submitted['review_comment']) === '') {
            throw new RuntimeException('Review comment was not persisted');
        }
        $reporterId = (int) $application['employer_user_id'];
        review_report($pdo,(int) $created['review_id'],$reporterId,'เนื้อหาไม่ตรงกับเหตุการณ์จริง');
        $reportCheck = $pdo->prepare("SELECT report_status FROM review_reports WHERE review_id=? AND reporter_user_id=?");
        $reportCheck->execute([(int) $created['review_id'],$reporterId]);
        if ($reportCheck->fetchColumn() !== 'pending') throw new RuntimeException('Review report was not persisted');
        try {
            review_report($pdo,(int) $created['review_id'],$reporterId,'ส่งรายงานซ้ำ');
            throw new RuntimeException('Duplicate review report was accepted');
        } catch (RuntimeException $exception) {
            if (!str_contains($exception->getMessage(), 'รายงานรีวิวนี้แล้ว')) throw $exception;
        }
        try {
            review_create_for_application($pdo,$applicationId,$reviewerId,4,'รีวิวซ้ำ');
            throw new RuntimeException('Duplicate review was accepted');
        } catch (RuntimeException $exception) {
            if (!str_contains($exception->getMessage(), 'รีวิวงานนี้ไปแล้ว')) throw $exception;
        }
    } finally {
        $pdo->rollBack();
    }
}

echo "account, review and subscription smoke test: PASS\n";
