<?php

declare(strict_types=1);

function matching_parse_skills(string $input): array
{
    $parts = preg_split('/[,;\n]+/u', $input) ?: [];
    $skills = [];
    foreach ($parts as $part) {
        $name = preg_replace('/\s+/u', ' ', trim($part));
        if ($name === '') continue;
        if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);
        $key = mb_strtolower($name, 'UTF-8');
        $skills[$key] = $name;
        if (count($skills) >= 30) break;
    }
    return array_values($skills);
}

function matching_skill_id(PDO $pdo, string $name): int
{
    $pdo->prepare('INSERT IGNORE INTO skills (skill_name) VALUES (?)')->execute([$name]);
    $statement = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_name=?');
    $statement->execute([$name]);
    return (int) $statement->fetchColumn();
}

function matching_sync_worker_skills(PDO $pdo, int $workerId, string $input): void
{
    $pdo->prepare('DELETE FROM worker_skills WHERE worker_user_id=?')->execute([$workerId]);
    $insert = $pdo->prepare('INSERT INTO worker_skills (worker_user_id,skill_id) VALUES (?,?)');
    foreach (matching_parse_skills($input) as $name) {
        $skillId = matching_skill_id($pdo, $name);
        if ($skillId) $insert->execute([$workerId, $skillId]);
    }
}

function matching_sync_job_skills(PDO $pdo, int $jobId, string $requiredInput, string $preferredInput): void
{
    $pdo->prepare('DELETE FROM job_skills WHERE job_id=?')->execute([$jobId]);
    $required = matching_parse_skills($requiredInput);
    $requiredKeys = array_fill_keys(array_map(fn(string $name): string => mb_strtolower($name, 'UTF-8'), $required), true);
    $insert = $pdo->prepare('INSERT INTO job_skills (job_id,skill_id,importance) VALUES (?,?,?)');

    foreach ($required as $name) {
        $insert->execute([$jobId, matching_skill_id($pdo, $name), 'required']);
    }
    foreach (matching_parse_skills($preferredInput) as $name) {
        if (isset($requiredKeys[mb_strtolower($name, 'UTF-8')])) continue;
        $insert->execute([$jobId, matching_skill_id($pdo, $name), 'preferred']);
    }
}

function matching_sync_worker_preferences(PDO $pdo, int $workerId, array $categorySlugs): void
{
    $pdo->prepare('DELETE FROM worker_job_preferences WHERE worker_user_id=?')->execute([$workerId]);
    $category = $pdo->prepare('SELECT job_category_id FROM job_categories WHERE category_slug=?');
    $insert = $pdo->prepare('INSERT INTO worker_job_preferences (worker_user_id,job_category_id) VALUES (?,?)');
    foreach (array_unique($categorySlugs) as $slug) {
        $category->execute([(string) $slug]);
        $categoryId = (int) $category->fetchColumn();
        if ($categoryId) $insert->execute([$workerId, $categoryId]);
    }
}

function matching_ids(string|null $value): array
{
    if (!$value) return [];
    return array_values(array_unique(array_map('intval', array_filter(explode(',', $value), 'strlen'))));
}

function matching_names(string|null $value): array
{
    return $value ? array_values(array_filter(array_map('trim', explode('||', $value)))) : [];
}

function matching_calculate(array $job, array $worker): array
{
    $workerSkillIds = matching_ids($worker['skill_ids'] ?? null);
    $requiredIds = matching_ids($job['required_skill_ids'] ?? null);
    $preferredIds = matching_ids($job['preferred_skill_ids'] ?? null);
    $requiredNames = matching_names($job['required_skill_names'] ?? null);
    $preferredNames = matching_names($job['preferred_skill_names'] ?? null);
    $earned = 0.0;
    $available = 0.0;
    $reasons = [];
    $missing = [];

    if ($requiredIds) {
        $available += 50;
        $matchedIds = array_intersect($requiredIds, $workerSkillIds);
        $earned += 50 * count($matchedIds) / count($requiredIds);
        $reasons[] = 'ตรงทักษะจำเป็น ' . count($matchedIds) . '/' . count($requiredIds);
        foreach ($requiredIds as $index => $skillId) {
            if (!in_array($skillId, $workerSkillIds, true) && isset($requiredNames[$index])) $missing[] = $requiredNames[$index];
        }
    }

    if ($preferredIds) {
        $available += 20;
        $matchedCount = count(array_intersect($preferredIds, $workerSkillIds));
        $earned += 20 * $matchedCount / count($preferredIds);
        if ($matchedCount) $reasons[] = 'ตรงทักษะเสริม ' . $matchedCount . '/' . count($preferredIds);
    }

    $workerProvince = trim((string) ($worker['work_province'] ?? ''));
    $jobProvince = trim((string) ($job['work_province'] ?? ''));
    $preferredMode = (string) ($worker['preferred_work_mode'] ?? 'any');
    $jobMode = (string) ($job['work_mode'] ?? 'onsite');
    if ($workerProvince !== '' || $preferredMode !== 'any') {
        $available += 20;
        $locationMatched = $jobMode === 'remote'
            ? in_array($preferredMode, ['any', 'remote'], true)
            : ($preferredMode === 'any' || $preferredMode === $jobMode)
                && $workerProvince !== '' && $jobProvince !== ''
                && mb_strtolower($workerProvince, 'UTF-8') === mb_strtolower($jobProvince, 'UTF-8');
        if ($locationMatched) {
            $earned += 20;
            $reasons[] = $jobMode === 'remote' ? 'รูปแบบงาน Remote ตรงกัน' : 'พื้นที่และรูปแบบงานตรงกัน';
        }
    }

    $preferenceIds = matching_ids($worker['preference_category_ids'] ?? null);
    if ($preferenceIds) {
        $available += 10;
        if (in_array((int) ($job['job_category_id'] ?? 0), $preferenceIds, true)) {
            $earned += 10;
            $reasons[] = 'ตรงประเภทงานที่สนใจ';
        }
    }

    return [
        'score' => $available > 0 ? (int) round($earned * 100 / $available) : null,
        'data_strength' => (int) round($available),
        'reasons' => $reasons,
        'missing_required' => $missing,
        'required_skills' => $requiredNames,
        'preferred_skills' => $preferredNames,
    ];
}

function matching_jobs_for_worker(PDO $pdo, int $workerId, int $limit = 6): array
{
    $workerStatement = $pdo->prepare("SELECT wp.work_province,wp.preferred_work_mode,
        GROUP_CONCAT(DISTINCT ws.skill_id ORDER BY ws.skill_id) skill_ids,
        GROUP_CONCAT(DISTINCT wjp.job_category_id ORDER BY wjp.job_category_id) preference_category_ids
        FROM worker_profiles wp
        LEFT JOIN worker_skills ws ON ws.worker_user_id=wp.user_id
        LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=wp.user_id
        WHERE wp.user_id=? GROUP BY wp.user_id");
    $workerStatement->execute([$workerId]);
    $worker = $workerStatement->fetch() ?: [];

    $jobs = $pdo->query("SELECT j.job_id AS id,j.job_id,j.job_category_id,j.job_title AS title,
        jc.category_slug AS job_type,j.work_location AS location,j.work_province,j.work_schedule AS work_date,
        j.work_mode,j.pay_amount,j.pay_unit,ep.company_name,ep.company_logo_path AS company_logo,
        (SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,
        (SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order,ji.job_image_id LIMIT 1) cover_image,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_id,NULL) ORDER BY s.skill_id) required_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_id,NULL) ORDER BY s.skill_id) preferred_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') required_skill_names,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') preferred_skill_names
        FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id
        JOIN job_categories jc ON jc.job_category_id=j.job_category_id
        LEFT JOIN job_skills js ON js.job_id=j.job_id LEFT JOIN skills s ON s.skill_id=js.skill_id
        WHERE j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())
        GROUP BY j.job_id ORDER BY j.created_at DESC LIMIT 200")->fetchAll();

    foreach ($jobs as &$job) $job['match'] = matching_calculate($job, $worker);
    unset($job);
    usort($jobs, function (array $a, array $b): int {
        $aScore = $a['match']['score'] ?? -1;
        $bScore = $b['match']['score'] ?? -1;
        return $bScore <=> $aScore ?: $b['id'] <=> $a['id'];
    });
    return array_slice($jobs, 0, max(1, $limit));
}

function matching_workers_for_job(PDO $pdo, int $jobId, int $employerId): array
{
    $jobStatement = $pdo->prepare("SELECT j.job_id,j.job_category_id,j.work_province,j.work_mode,j.job_title,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_id,NULL) ORDER BY s.skill_id) required_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_id,NULL) ORDER BY s.skill_id) preferred_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') required_skill_names,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') preferred_skill_names
        FROM jobs j LEFT JOIN job_skills js ON js.job_id=j.job_id LEFT JOIN skills s ON s.skill_id=js.skill_id
        WHERE j.job_id=? AND j.employer_user_id=? GROUP BY j.job_id");
    $jobStatement->execute([$jobId, $employerId]);
    $job = $jobStatement->fetch();
    if (!$job) return [];

    $statement = $pdo->prepare("SELECT u.user_id,CONCAT(u.first_name,' ',u.last_name) name,
        wp.professional_headline headline,wp.biography,wp.profile_image_path,wp.work_province,wp.preferred_work_mode,wp.available_from,
        GROUP_CONCAT(DISTINCT ws.skill_id ORDER BY ws.skill_id) skill_ids,
        GROUP_CONCAT(DISTINCT sk.skill_name ORDER BY sk.skill_name SEPARATOR '||') skill_names,
        GROUP_CONCAT(DISTINCT wjp.job_category_id ORDER BY wjp.job_category_id) preference_category_ids,
        ji.invitation_status,
        (SELECT a.application_status FROM applications a WHERE a.job_id=? AND a.worker_user_id=u.user_id LIMIT 1) application_status,
        EXISTS(SELECT 1 FROM applications a WHERE a.job_id=? AND a.worker_user_id=u.user_id) has_applied
        FROM users u JOIN worker_profiles wp ON wp.user_id=u.user_id
        LEFT JOIN worker_skills ws ON ws.worker_user_id=u.user_id LEFT JOIN skills sk ON sk.skill_id=ws.skill_id
        LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=u.user_id
        LEFT JOIN job_invitations ji ON ji.job_id=? AND ji.worker_user_id=u.user_id
        WHERE u.role='worker' AND u.account_status='active' AND wp.profile_visibility='searchable'
        GROUP BY u.user_id ORDER BY u.created_at DESC LIMIT 300");
    $statement->execute([$jobId, $jobId, $jobId]);
    $workers = $statement->fetchAll();
    foreach ($workers as &$worker) $worker['match'] = matching_calculate($job, $worker);
    unset($worker);
    usort($workers, function (array $a, array $b): int {
        $aScore = $a['match']['score'] ?? -1;
        $bScore = $b['match']['score'] ?? -1;
        return $bScore <=> $aScore ?: $a['name'] <=> $b['name'];
    });
    return $workers;
}

function matching_employer_is_verified(PDO $pdo, int $employerId): bool
{
    $statement = $pdo->prepare("SELECT document_status FROM employer_documents WHERE employer_user_id=? ORDER BY submitted_at DESC,employer_document_id DESC LIMIT 1");
    $statement->execute([$employerId]);
    return $statement->fetchColumn() === 'approved';
}

function matching_send_invitation(PDO $pdo, int $employerId, int $jobId, int $workerId, string $message): void
{
    if (!matching_employer_is_verified($pdo, $employerId)) throw new RuntimeException('บัญชีผู้ว่าจ้างต้องผ่านการยืนยันก่อนส่งคำเชิญ');
    $jobStatement = $pdo->prepare("SELECT job_title FROM jobs WHERE job_id=? AND employer_user_id=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
    $jobStatement->execute([$jobId, $employerId]);
    $jobTitle = $jobStatement->fetchColumn();
    if (!$jobTitle) throw new RuntimeException('ไม่พบประกาศงานที่เปิดรับสมัคร');

    $workerStatement = $pdo->prepare("SELECT CONCAT(first_name,' ',last_name) FROM users u JOIN worker_profiles wp ON wp.user_id=u.user_id WHERE u.user_id=? AND u.role='worker' AND u.account_status='active' AND wp.profile_visibility='searchable'");
    $workerStatement->execute([$workerId]);
    if (!$workerStatement->fetchColumn()) throw new RuntimeException('ผู้หางานรายนี้ไม่เปิดให้ค้นหาโปรไฟล์');

    $appliedStatement = $pdo->prepare('SELECT 1 FROM applications WHERE job_id=? AND worker_user_id=?');
    $appliedStatement->execute([$jobId, $workerId]);
    if ($appliedStatement->fetchColumn()) throw new RuntimeException('ผู้หางานรายนี้สมัครงานแล้ว');

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO job_invitations (job_id,worker_user_id,invitation_message) VALUES (?,?,?)')
            ->execute([$jobId, $workerId, trim($message) ?: null]);
        $pdo->prepare('INSERT INTO notifications (user_id,notification_title,notification_message,notification_url) VALUES (?,?,?,?)')
            ->execute([$workerId, 'คำเชิญสมัครงานใหม่', 'ผู้ว่าจ้างเชิญคุณสมัครงาน: ' . $jobTitle, 'worker/invitations.php']);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ((string) $e->getCode() === '23000') throw new RuntimeException('ส่งคำเชิญสำหรับงานนี้ไปแล้ว');
        throw $e;
    }
}
