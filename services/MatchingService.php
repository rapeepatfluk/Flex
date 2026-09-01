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

function matching_skill_id(PDO $pdo, string $name, bool $isCustom = false): int
{
    $pdo->prepare('INSERT INTO skills (skill_name,is_custom,is_active) VALUES (?,?,1) ON DUPLICATE KEY UPDATE skill_id=LAST_INSERT_ID(skill_id),is_active=1')
        ->execute([$name, $isCustom ? 1 : 0]);
    $statement = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_name=?');
    $statement->execute([$name]);
    return (int) $statement->fetchColumn();
}

function matching_skill_catalog(PDO $pdo, array $selectedSkillIds = []): array
{
    $statement = $pdo->query('SELECT c.skill_category_id,c.category_name,c.category_slug,s.skill_id,s.skill_name FROM skill_categories c LEFT JOIN skills s ON s.skill_category_id=c.skill_category_id AND s.is_active=1 WHERE c.is_active=1 ORDER BY c.sort_order,c.category_name,s.skill_name');
    $catalog = [];
    foreach ($statement->fetchAll() as $row) {
        $categoryId = (int) $row['skill_category_id'];
        if (!isset($catalog[$categoryId])) {
            $catalog[$categoryId] = ['name' => $row['category_name'], 'slug' => $row['category_slug'], 'skills' => []];
        }
        if ($row['skill_id'] !== null) {
            $catalog[$categoryId]['skills'][] = ['id' => (int) $row['skill_id'], 'name' => $row['skill_name']];
        }
    }

    $selectedSkillIds = array_values(array_unique(array_filter(array_map('intval', $selectedSkillIds), fn(int $id): bool => $id > 0)));
    if ($selectedSkillIds) {
        $placeholders = implode(',', array_fill(0, count($selectedSkillIds), '?'));
        $customStatement = $pdo->prepare("SELECT skill_id,skill_name FROM skills WHERE skill_category_id IS NULL AND skill_id IN ({$placeholders}) ORDER BY skill_name");
        $customStatement->execute($selectedSkillIds);
        $customSkills = $customStatement->fetchAll();
        if ($customSkills) {
            $catalog['custom'] = ['name' => 'อื่น ๆ ที่เพิ่มเอง', 'slug' => 'custom', 'skills' => array_map(fn(array $skill): array => ['id' => (int) $skill['skill_id'], 'name' => $skill['skill_name']], $customSkills)];
        }
    }
    return $catalog;
}

function matching_selected_skill_ids(PDO $pdo, array $rawSkillIds, string $customInput = '', int $limit = 20): array
{
    $skillIds = array_values(array_unique(array_filter(array_map('intval', $rawSkillIds), fn(int $id): bool => $id > 0)));
    if (count($skillIds) > $limit) throw new RuntimeException("เลือกทักษะได้สูงสุด {$limit} รายการ");

    if ($skillIds) {
        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $statement = $pdo->prepare("SELECT skill_id FROM skills WHERE is_active=1 AND skill_id IN ({$placeholders})");
        $statement->execute($skillIds);
        $validIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
        if (count($validIds) !== count($skillIds)) throw new RuntimeException('มีทักษะที่เลือกไม่ถูกต้อง');
        $skillIds = $validIds;
    }

    $customSkills = matching_parse_skills($customInput);
    $existingNames = [];
    if ($skillIds) {
        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $statement = $pdo->prepare("SELECT skill_name FROM skills WHERE skill_id IN ({$placeholders})");
        $statement->execute($skillIds);
        $existingNames = array_map(fn(string $name): string => mb_strtolower($name, 'UTF-8'), $statement->fetchAll(PDO::FETCH_COLUMN));
    }
    foreach ($customSkills as $name) {
        if (in_array(mb_strtolower($name, 'UTF-8'), $existingNames, true)) continue;
        $skillIds[] = matching_skill_id($pdo, $name, true);
        $existingNames[] = mb_strtolower($name, 'UTF-8');
    }
    $skillIds = array_values(array_unique($skillIds));
    if (count($skillIds) > $limit) throw new RuntimeException("เลือกทักษะได้สูงสุด {$limit} รายการ");
    return $skillIds;
}

function matching_skill_names(PDO $pdo, array $skillIds): array
{
    if (!$skillIds) return [];
    $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
    $statement = $pdo->prepare("SELECT skill_id,skill_name FROM skills WHERE skill_id IN ({$placeholders})");
    $statement->execute($skillIds);
    $namesById = [];
    foreach ($statement->fetchAll() as $row) $namesById[(int) $row['skill_id']] = $row['skill_name'];
    return array_values(array_filter(array_map(fn(int $id): ?string => $namesById[$id] ?? null, $skillIds)));
}

function matching_sync_job_skill_assignments(PDO $pdo, int $jobId, array $skillIds, array $importanceBySkill, string $customSkills = '', string $customImportance = 'required'): void
{
    $selectedIds = matching_selected_skill_ids($pdo, $skillIds);
    $customIds = matching_selected_skill_ids($pdo, [], $customSkills);
    $requiredIds = [];
    $preferredIds = [];

    foreach ($selectedIds as $skillId) {
        if (($importanceBySkill[$skillId] ?? 'required') === 'preferred') {
            $preferredIds[] = $skillId;
        } else {
            $requiredIds[] = $skillId;
        }
    }
    foreach ($customIds as $skillId) {
        if ($customImportance === 'required') {
            $requiredIds[] = $skillId;
        } else {
            $preferredIds[] = $skillId;
        }
    }

    $requiredIds = array_values(array_unique($requiredIds));
    $preferredIds = array_values(array_diff(array_unique($preferredIds), $requiredIds));
    matching_sync_job_skill_selection($pdo, $jobId, $requiredIds, '', $preferredIds, '');
}

function matching_sync_worker_skill_selection(PDO $pdo, int $workerId, array $skillIds, string $customInput = ''): array
{
    $skillIds = matching_selected_skill_ids($pdo, $skillIds, $customInput);
    $pdo->prepare('DELETE FROM worker_skills WHERE worker_user_id=?')->execute([$workerId]);
    $insert = $pdo->prepare('INSERT INTO worker_skills (worker_user_id,skill_id) VALUES (?,?)');
    foreach ($skillIds as $skillId) $insert->execute([$workerId, $skillId]);
    return matching_skill_names($pdo, $skillIds);
}

function matching_sync_job_skill_selection(PDO $pdo, int $jobId, array $requiredIds, string $customRequired, array $preferredIds, string $customPreferred): void
{
    $requiredIds = matching_selected_skill_ids($pdo, $requiredIds, $customRequired);
    $preferredIds = array_values(array_diff(matching_selected_skill_ids($pdo, $preferredIds, $customPreferred), $requiredIds));
    if (count($requiredIds) + count($preferredIds) > 20) throw new RuntimeException('เลือกทักษะรวมได้สูงสุด 20 รายการ');

    $pdo->prepare('DELETE FROM job_skills WHERE job_id=?')->execute([$jobId]);
    $insert = $pdo->prepare('INSERT INTO job_skills (job_id,skill_id,importance) VALUES (?,?,?)');
    foreach ($requiredIds as $skillId) $insert->execute([$jobId, $skillId, 'required']);
    foreach ($preferredIds as $skillId) $insert->execute([$jobId, $skillId, 'preferred']);
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

function matching_work_interests(PDO $pdo): array
{
    return $pdo->query('SELECT work_interest_id,interest_slug,interest_name FROM work_interests WHERE is_active=1 ORDER BY sort_order,interest_name')->fetchAll();
}

function matching_work_interest_exists(PDO $pdo, int $interestId): bool
{
    if ($interestId < 1) return false;
    $statement = $pdo->prepare('SELECT 1 FROM work_interests WHERE work_interest_id=? AND is_active=1');
    $statement->execute([$interestId]);
    return (bool) $statement->fetchColumn();
}

function matching_sync_worker_work_interests(PDO $pdo, int $workerId, array $interestIds): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $interestIds), fn(int $id): bool => $id > 0)));
    if (count($ids) > 5) throw new RuntimeException('เลือกงานที่สนใจได้ไม่เกิน 5 หมวด');

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $pdo->prepare("SELECT work_interest_id FROM work_interests WHERE is_active=1 AND work_interest_id IN ({$placeholders})");
        $statement->execute($ids);
        $validIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
        if (count($validIds) !== count($ids)) throw new RuntimeException('หมวดงานที่สนใจไม่ถูกต้อง');
    }

    $pdo->prepare('DELETE FROM worker_work_interests WHERE worker_user_id=?')->execute([$workerId]);
    $insert = $pdo->prepare('INSERT INTO worker_work_interests (worker_user_id,work_interest_id) VALUES (?,?)');
    foreach ($ids as $interestId) $insert->execute([$workerId, $interestId]);
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
    $workerInterestIds = matching_ids($worker['work_interest_ids'] ?? null);
    $requiredNames = matching_names($job['required_skill_names'] ?? null);
    $preferredNames = matching_names($job['preferred_skill_names'] ?? null);
    $earned = 0.0;
    $available = 0.0;
    $reasons = [];
    $missing = [];

    if ($requiredIds) {
        $available += 40;
        $matchedIds = array_intersect($requiredIds, $workerSkillIds);
        $earned += 40 * count($matchedIds) / count($requiredIds);
        $reasons[] = 'ตรงทักษะจำเป็น ' . count($matchedIds) . '/' . count($requiredIds);
        foreach ($requiredIds as $index => $skillId) {
            if (!in_array($skillId, $workerSkillIds, true) && isset($requiredNames[$index])) $missing[] = $requiredNames[$index];
        }
    }

    if ($preferredIds) {
        $available += 15;
        $matchedCount = count(array_intersect($preferredIds, $workerSkillIds));
        $earned += 15 * $matchedCount / count($preferredIds);
        if ($matchedCount) $reasons[] = 'ตรงทักษะเสริม ' . $matchedCount . '/' . count($preferredIds);
    }

    $jobInterestId = (int) ($job['work_interest_id'] ?? 0);
    if ($jobInterestId && $workerInterestIds) {
        $available += 25;
        if (in_array($jobInterestId, $workerInterestIds, true)) {
            $earned += 25;
            $interestName = trim((string) ($job['work_interest_name'] ?? ''));
            $reasons[] = $interestName !== '' ? 'สนใจงานด้าน: ' . $interestName : 'ตรงกับงานที่สนใจ';
        }
    }

    $preferredMode = (string) ($worker['preferred_work_mode'] ?? 'any');
    $jobMode = (string) ($job['work_mode'] ?? 'onsite');
    if (array_key_exists('preferred_work_mode', $worker)) {
        $available += 10;
        if ($preferredMode === 'any' || $preferredMode === $jobMode) {
            $earned += 10;
            $reasons[] = 'รูปแบบงานตรงกับที่ต้องการ';
        }
    }

    $preferenceIds = matching_ids($worker['preference_category_ids'] ?? null);
    if ($preferenceIds) {
        $available += 10;
        if (in_array((int) ($job['job_category_id'] ?? 0), $preferenceIds, true)) {
            $earned += 10;
            $reasons[] = 'ตรงรูปแบบการจ้างที่สนใจ';
        }
    }

    $hasWorkerMatchingData = $workerSkillIds
        || $workerInterestIds
        || $preferenceIds
        || trim((string) ($worker['work_province'] ?? '')) !== '';

    return [
        'score' => $available > 0 && $hasWorkerMatchingData ? (int) round($earned * 100 / $available) : null,
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
        GROUP_CONCAT(DISTINCT wjp.job_category_id ORDER BY wjp.job_category_id) preference_category_ids,
        GROUP_CONCAT(DISTINCT wwi.work_interest_id ORDER BY wwi.work_interest_id) work_interest_ids
        FROM worker_profiles wp
        LEFT JOIN worker_skills ws ON ws.worker_user_id=wp.user_id
        LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=wp.user_id
        LEFT JOIN worker_work_interests wwi ON wwi.worker_user_id=wp.user_id
        WHERE wp.user_id=? GROUP BY wp.user_id");
    $workerStatement->execute([$workerId]);
    $worker = $workerStatement->fetch() ?: [];

    $jobsStatement = $pdo->prepare("SELECT j.job_id AS id,j.job_id,j.job_category_id,j.work_interest_id,wi.interest_name work_interest_name,j.job_title AS title,j.created_at,
        jc.category_slug AS job_type,j.work_location AS location,j.work_province,j.work_schedule AS work_date,
        j.work_mode,j.pay_amount,j.pay_unit,ep.company_name,ep.company_logo_path AS company_logo,
        (SELECT ROUND(AVG(a.rating_by_worker), 1) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) employer_rating_average,
        (SELECT COUNT(a.rating_by_worker) FROM applications a JOIN jobs rated_jobs ON rated_jobs.job_id=a.job_id WHERE rated_jobs.employer_user_id=j.employer_user_id AND a.rating_by_worker IS NOT NULL) employer_rating_count,
        (SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,
        (SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order,ji.job_image_id LIMIT 1) cover_image,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_id,NULL) ORDER BY s.skill_id) required_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_id,NULL) ORDER BY s.skill_id) preferred_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') required_skill_names,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') preferred_skill_names
        FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id
        JOIN job_categories jc ON jc.job_category_id=j.job_category_id
        LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id
        LEFT JOIN job_skills js ON js.job_id=j.job_id LEFT JOIN skills s ON s.skill_id=js.skill_id
        WHERE j.job_status='published' AND j.work_province=? AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE())
        GROUP BY j.job_id ORDER BY j.created_at DESC LIMIT 200");
    $jobsStatement->execute([FLEXJOB_PROVINCE]);
    $jobs = $jobsStatement->fetchAll();

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
    $jobStatement = $pdo->prepare("SELECT j.job_id,j.job_category_id,j.work_interest_id,wi.interest_name work_interest_name,j.work_province,j.work_mode,j.job_title,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_id,NULL) ORDER BY s.skill_id) required_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_id,NULL) ORDER BY s.skill_id) preferred_skill_ids,
        GROUP_CONCAT(DISTINCT IF(js.importance='required',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') required_skill_names,
        GROUP_CONCAT(DISTINCT IF(js.importance='preferred',s.skill_name,NULL) ORDER BY s.skill_id SEPARATOR '||') preferred_skill_names
        FROM jobs j LEFT JOIN work_interests wi ON wi.work_interest_id=j.work_interest_id
        LEFT JOIN job_skills js ON js.job_id=j.job_id LEFT JOIN skills s ON s.skill_id=js.skill_id
        WHERE j.job_id=? AND j.employer_user_id=? AND j.work_province=? GROUP BY j.job_id");
    $jobStatement->execute([$jobId, $employerId, FLEXJOB_PROVINCE]);
    $job = $jobStatement->fetch();
    if (!$job) return [];

    $statement = $pdo->prepare("SELECT u.user_id,CONCAT(u.first_name,' ',u.last_name) name,
        wp.professional_headline headline,wp.biography,wp.profile_image_path,wp.work_province,wp.preferred_work_mode,wp.available_from,
        GROUP_CONCAT(DISTINCT ws.skill_id ORDER BY ws.skill_id) skill_ids,
        GROUP_CONCAT(DISTINCT sk.skill_name ORDER BY sk.skill_name SEPARATOR '||') skill_names,
        GROUP_CONCAT(DISTINCT wjp.job_category_id ORDER BY wjp.job_category_id) preference_category_ids,
        GROUP_CONCAT(DISTINCT wwi.work_interest_id ORDER BY wwi.work_interest_id) work_interest_ids,
        GROUP_CONCAT(DISTINCT wi.interest_name ORDER BY wi.sort_order SEPARATOR '||') work_interest_names,
        ji.invitation_status,
        (SELECT a.application_status FROM applications a WHERE a.job_id=? AND a.worker_user_id=u.user_id LIMIT 1) application_status,
        EXISTS(SELECT 1 FROM applications a WHERE a.job_id=? AND a.worker_user_id=u.user_id) has_applied
        FROM users u JOIN worker_profiles wp ON wp.user_id=u.user_id
        LEFT JOIN worker_skills ws ON ws.worker_user_id=u.user_id LEFT JOIN skills sk ON sk.skill_id=ws.skill_id
        LEFT JOIN worker_job_preferences wjp ON wjp.worker_user_id=u.user_id
        LEFT JOIN worker_work_interests wwi ON wwi.worker_user_id=u.user_id
        LEFT JOIN work_interests wi ON wi.work_interest_id=wwi.work_interest_id
        LEFT JOIN job_invitations ji ON ji.job_id=? AND ji.worker_user_id=u.user_id
        WHERE u.role='worker' AND u.account_status='active' AND wp.profile_visibility='searchable' AND wp.work_province=?
        GROUP BY u.user_id ORDER BY u.created_at DESC LIMIT 300");
    $statement->execute([$jobId, $jobId, $jobId, FLEXJOB_PROVINCE]);
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
    $jobStatement = $pdo->prepare("SELECT job_title FROM jobs WHERE job_id=? AND employer_user_id=? AND work_province=? AND job_status='published' AND (application_deadline IS NULL OR application_deadline>=CURDATE())");
    $jobStatement->execute([$jobId, $employerId, FLEXJOB_PROVINCE]);
    $jobTitle = $jobStatement->fetchColumn();
    if (!$jobTitle) throw new RuntimeException('ไม่พบประกาศงานที่เปิดรับสมัคร');

    $workerStatement = $pdo->prepare("SELECT CONCAT(first_name,' ',last_name) FROM users u JOIN worker_profiles wp ON wp.user_id=u.user_id WHERE u.user_id=? AND u.role='worker' AND u.account_status='active' AND wp.profile_visibility='searchable' AND wp.work_province=?");
    $workerStatement->execute([$workerId, FLEXJOB_PROVINCE]);
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
