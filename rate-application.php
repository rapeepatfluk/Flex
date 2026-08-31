<?php
require_once __DIR__ . '/config/config.php';
require_login();

$role = user()['role'];
if (!in_array($role, ['worker', 'employer'], true) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$applicationId = (int) ($_POST['application_id'] ?? 0);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$redirectPath = $role === 'worker'
    ? 'worker/application-detail.php?id=' . $applicationId
    : 'employer/dashboard.php';

try {
    verify_csrf();
    if (!$applicationId || $rating === false || $rating === null) {
        throw new RuntimeException('กรุณาเลือกคะแนน 1–5 ดาว');
    }

    $pdo = db();
    $statement = $pdo->prepare('SELECT a.application_id,a.application_status,a.worker_user_id,j.job_id,j.employer_user_id FROM applications a JOIN jobs j ON j.job_id=a.job_id WHERE a.application_id=?');
    $statement->execute([$applicationId]);
    $application = $statement->fetch();

    if (!$application || $application['application_status'] !== 'completed') {
        throw new RuntimeException('ให้คะแนนได้เฉพาะงานที่เสร็จสิ้นแล้ว');
    }

    $currentUserId = (int) user()['id'];
    if ($role === 'worker' && $currentUserId === (int) $application['worker_user_id']) {
        $ratedUserId = (int) $application['employer_user_id'];
        $redirectPath = 'worker/application-detail.php?id=' . $applicationId;
    } elseif ($role === 'employer' && $currentUserId === (int) $application['employer_user_id']) {
        $ratedUserId = (int) $application['worker_user_id'];
        $redirectPath = 'employer/applicant-detail.php?id=' . $applicationId . '&job=' . $application['job_id'];
    } else {
        throw new RuntimeException('คุณไม่มีสิทธิ์ให้คะแนนสำหรับงานนี้');
    }

    $ratingColumn = $role === 'worker' ? 'rating_by_worker' : 'rating_by_employer';
    $ratedAtColumn = $role === 'worker' ? 'rated_by_worker_at' : 'rated_by_employer_at';
    $update = $pdo->prepare("UPDATE applications SET {$ratingColumn}=?, {$ratedAtColumn}=NOW() WHERE application_id=? AND {$ratingColumn} IS NULL");
    $update->execute([$rating, $applicationId]);
    if (!$update->rowCount()) {
        throw new RuntimeException('คุณให้คะแนนสำหรับงานนี้ไปแล้ว');
    }

    $notificationUrl = $role === 'worker'
        ? 'employer/applicant-detail.php?id=' . $applicationId . '&job=' . $application['job_id']
        : 'worker/application-detail.php?id=' . $applicationId;
    $pdo->prepare('INSERT INTO notifications (user_id,notification_title,notification_message,notification_url) VALUES (?,?,?,?)')
        ->execute([$ratedUserId, 'ได้รับคะแนนใหม่', user()['name'] . ' ให้คะแนนคุณ ' . $rating . ' ดาว หลังจบงาน', $notificationUrl]);
    flash('success', 'บันทึกคะแนน ' . $rating . ' ดาวเรียบร้อยแล้ว');
} catch (PDOException $exception) {
    flash('error', 'ไม่สามารถบันทึกคะแนนได้ กรุณาลองใหม่');
} catch (RuntimeException $exception) {
    flash('error', $exception->getMessage());
}

redirect($redirectPath);