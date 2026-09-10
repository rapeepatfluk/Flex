<?php

declare(strict_types=1);

function review_validate_comment(string $comment): string
{
    $comment = preg_replace('/\s+/u', ' ', trim($comment)) ?? '';
    if ($comment === '') throw new RuntimeException('กรุณาเขียนความคิดเห็นประกอบคะแนน');
    if (mb_strlen($comment, 'UTF-8') > 1000) throw new RuntimeException('ความคิดเห็นต้องมีความยาวไม่เกิน 1,000 ตัวอักษร');
    return $comment;
}

/** @return array{review_id:int,reviewee_user_id:int,reviewer_role:string} */
function review_create_for_application(PDO $pdo, int $applicationId, int $reviewerId, int $rating, string $comment): array
{
    if ($rating < 1 || $rating > 5) throw new RuntimeException('กรุณาเลือกคะแนน 1–5 ดาว');
    $comment = review_validate_comment($comment);

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare("SELECT a.application_id,a.application_status,a.worker_user_id,j.employer_user_id
            FROM applications a JOIN jobs j ON j.job_id=a.job_id
            WHERE a.application_id=? FOR UPDATE");
        $statement->execute([$applicationId]);
        $application = $statement->fetch();
        if (!$application || $application['application_status'] !== 'completed') {
            throw new RuntimeException('รีวิวได้เฉพาะงานที่เสร็จสิ้นแล้ว');
        }

        if ($reviewerId === (int) $application['worker_user_id']) {
            $reviewerRole = 'worker';
            $revieweeId = (int) $application['employer_user_id'];
            $legacyRatingColumn = 'rating_by_worker';
            $legacyRatedAtColumn = 'rated_by_worker_at';
        } elseif ($reviewerId === (int) $application['employer_user_id']) {
            $reviewerRole = 'employer';
            $revieweeId = (int) $application['worker_user_id'];
            $legacyRatingColumn = 'rating_by_employer';
            $legacyRatedAtColumn = 'rated_by_employer_at';
        } else {
            throw new RuntimeException('คุณไม่มีสิทธิ์รีวิวงานนี้');
        }

        $insert = $pdo->prepare('INSERT INTO reviews (application_id,reviewer_user_id,reviewee_user_id,reviewer_role,rating,review_comment) VALUES (?,?,?,?,?,?)');
        try {
            $insert->execute([$applicationId, $reviewerId, $revieweeId, $reviewerRole, $rating, $comment]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') throw new RuntimeException('คุณรีวิวงานนี้ไปแล้ว');
            throw $exception;
        }
        $reviewId = (int) $pdo->lastInsertId();

        // Keep the legacy rating columns in sync during the transition so old reports remain compatible.
        $pdo->prepare("UPDATE applications SET {$legacyRatingColumn}=?,{$legacyRatedAtColumn}=NOW() WHERE application_id=?")
            ->execute([$rating, $applicationId]);

        if ($ownsTransaction) $pdo->commit();
        return ['review_id' => $reviewId, 'reviewee_user_id' => $revieweeId, 'reviewer_role' => $reviewerRole];
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

/** @return array{average:float|int|string|null,count:int|string} */
function review_received_summary(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare("SELECT ROUND(AVG(rating),1) average,COUNT(*) count FROM reviews WHERE reviewee_user_id=? AND review_status='visible'");
    $statement->execute([$userId]);
    return $statement->fetch() ?: ['average' => null, 'count' => 0];
}

function review_submitted_for_application(PDO $pdo, int $applicationId, int $reviewerId): ?array
{
    $statement = $pdo->prepare('SELECT review_id,rating,review_comment,created_at FROM reviews WHERE application_id=? AND reviewer_user_id=? LIMIT 1');
    $statement->execute([$applicationId, $reviewerId]);
    return $statement->fetch() ?: null;
}

function review_received_list(PDO $pdo, int $userId, int $limit = 20): array
{
    $limit = max(1, min(100, $limit));
    $statement = $pdo->prepare("SELECT r.review_id,r.rating,r.review_comment,r.created_at,r.reviewer_user_id,r.reviewer_role,
            CASE WHEN r.reviewer_role='employer' THEN COALESCE(ep.company_name,CONCAT(u.first_name,' ',u.last_name))
                 ELSE CONCAT(u.first_name,' ',u.last_name) END reviewer_name,
            j.job_title
        FROM reviews r
        JOIN users u ON u.user_id=r.reviewer_user_id
        JOIN applications a ON a.application_id=r.application_id
        JOIN jobs j ON j.job_id=a.job_id
        LEFT JOIN employer_profiles ep ON ep.user_id=r.reviewer_user_id
        WHERE r.reviewee_user_id=? AND r.review_status='visible'
        ORDER BY r.created_at DESC,r.review_id DESC LIMIT {$limit}");
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function review_report(PDO $pdo, int $reviewId, int $reporterId, string $reason): void
{
    $reason = preg_replace('/\s+/u', ' ', trim($reason)) ?? '';
    if ($reason === '' || mb_strlen($reason, 'UTF-8') > 500) throw new RuntimeException('กรุณาระบุเหตุผลไม่เกิน 500 ตัวอักษร');
    $reviewStatement = $pdo->prepare("SELECT reviewer_user_id FROM reviews WHERE review_id=? AND review_status='visible'");
    $reviewStatement->execute([$reviewId]);
    $reviewerId = $reviewStatement->fetchColumn();
    if ($reviewerId === false) throw new RuntimeException('ไม่พบรีวิวที่ต้องการรายงาน');
    if ((int) $reviewerId === $reporterId) throw new RuntimeException('ไม่สามารถรายงานรีวิวของตนเองได้');
    try {
        $pdo->prepare('INSERT INTO review_reports (review_id,reporter_user_id,report_reason) VALUES (?,?,?)')
            ->execute([$reviewId, $reporterId, $reason]);
    } catch (PDOException $exception) {
        if ((string) $exception->getCode() === '23000') throw new RuntimeException('คุณรายงานรีวิวนี้แล้ว');
        throw $exception;
    }
}
