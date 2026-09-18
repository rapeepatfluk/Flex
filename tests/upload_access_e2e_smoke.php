<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

const E2E_BASE_URL = 'http://localhost/Flex';

function e2e_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

/** @return array{status:int,headers:string,body:string} */
function e2e_request(string $url, ?string $cookieFile = null, ?array $post = null, bool $follow = true): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($cookieFile) {
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    $response = curl_exec($curl);
    if ($response === false) throw new RuntimeException('HTTP request failed: ' . curl_error($curl));
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    curl_close($curl);
    return [
        'status' => $status,
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize),
    ];
}

function e2e_csrf(string $body): string
{
    if (!preg_match('/name="csrf_token" value="([^"]+)"/', $body, $matches)) {
        throw new RuntimeException('CSRF token was not found');
    }
    return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
}

function e2e_login(string $identifier, string $password): string
{
    $cookieFile = tempnam(sys_get_temp_dir(), 'flexjob-upload-cookie-');
    $login = e2e_request(E2E_BASE_URL . '/auth/login.php', $cookieFile);
    $response = e2e_request(E2E_BASE_URL . '/auth/login.php', $cookieFile, [
        'csrf_token' => e2e_csrf($login['body']),
        'identifier' => $identifier,
        'password' => $password,
    ]);
    e2e_assert(
        $response['status'] === 200 && !str_contains($response['body'], 'อีเมลหรือชื่อผู้ใช้') && !str_contains($response['body'], 'เข้าสู่ระบบเพื่อจัดการงานของคุณ'),
        'Login did not complete for ' . $identifier
    );
    return $cookieFile;
}

function e2e_create_user(PDO $pdo, string $username, string $role): int
{
    $passwordHash = password_hash('UploadAccess!2026', PASSWORD_DEFAULT);
    $statement = $pdo->prepare("INSERT INTO users
        (username,first_name,last_name,email,password_hash,role,account_status,email_verified_at)
        VALUES (?,?,?,?,?,?, 'active', NOW())");
    $statement->execute([
        $username,
        ucfirst($role),
        'Upload Test',
        $username . '@flexjob.local',
        $passwordHash,
        $role,
    ]);
    return (int) $pdo->lastInsertId();
}

function e2e_is_pdf_response(array $response): bool
{
    return $response['status'] === 200
        && str_contains(strtolower($response['headers']), 'content-type: application/pdf')
        && str_starts_with($response['body'], '%PDF');
}

function e2e_add_employer_profile(PDO $pdo, int $userId, bool $verified): void
{
    $pdo->prepare("INSERT INTO employer_profiles (user_id,company_name) VALUES (?,?)")
        ->execute([$userId, 'Upload Access Test Co.']);
    if ($verified) {
        $pdo->prepare("INSERT INTO employer_documents (employer_user_id,document_file_path,document_status,submitted_at)
            VALUES (?,'tests/upload-access-fixture.pdf','approved',NOW())")->execute([$userId]);
    }
}

$pdo = db();
$suffix = bin2hex(random_bytes(5));
$password = 'UploadAccess!2026';
$workerUsername = 'w.' . $suffix;
$verifiedEmployerUsername = 'e.' . $suffix;
$unverifiedEmployerUsername = 'u.' . $suffix;
$otherEmployerUsername = 'o.' . $suffix;
$adminUsername = 'a.' . $suffix;
$workerId = $verifiedEmployerId = $unverifiedEmployerId = $otherEmployerId = $adminId = $jobId = $applicationId = 0;
$resumePath = $portfolioPath = null;
$cookies = [];
$fixturePdf = tempnam(sys_get_temp_dir(), 'flexjob-upload-') . '.pdf';
$fixtureTxt = tempnam(sys_get_temp_dir(), 'flexjob-upload-') . '.txt';

try {
    // A small but valid PDF fixture. The upload endpoint validates the MIME type.
    file_put_contents($fixturePdf, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
    file_put_contents($fixtureTxt, 'This file must be rejected by the upload allow-list.');

    $workerId = e2e_create_user($pdo, $workerUsername, 'worker');
    $verifiedEmployerId = e2e_create_user($pdo, $verifiedEmployerUsername, 'employer');
    $unverifiedEmployerId = e2e_create_user($pdo, $unverifiedEmployerUsername, 'employer');
    $otherEmployerId = e2e_create_user($pdo, $otherEmployerUsername, 'employer');
    $adminId = e2e_create_user($pdo, $adminUsername, 'admin');

    $pdo->prepare("INSERT INTO worker_profiles
        (user_id,professional_headline,profile_visibility,work_province,preferred_work_mode)
        VALUES (?,'Upload access fixture','searchable',?,'any')")->execute([$workerId, FLEXJOB_PROVINCE]);
    e2e_add_employer_profile($pdo, $verifiedEmployerId, true);
    e2e_add_employer_profile($pdo, $unverifiedEmployerId, false);
    e2e_add_employer_profile($pdo, $otherEmployerId, true);

    $categoryId = (int) $pdo->query('SELECT job_category_id FROM job_categories ORDER BY job_category_id LIMIT 1')->fetchColumn();
    e2e_assert($categoryId > 0, 'Job category fixture is missing');
    $insertJob = $pdo->prepare("INSERT INTO jobs
        (employer_user_id,job_category_id,job_title,job_description,work_location,work_province,application_deadline,pay_amount,pay_unit,open_positions,job_status)
        VALUES (?,?,'Upload access fixture','Test job for file access','บุรีรัมย์',?,DATE_ADD(CURDATE(),INTERVAL 30 DAY),500,'day',1,'published')");
    $insertJob->execute([$verifiedEmployerId, $categoryId, FLEXJOB_PROVINCE]);
    $jobId = (int) $pdo->lastInsertId();
    $insertJob->execute([$unverifiedEmployerId, $categoryId, FLEXJOB_PROVINCE]);
    $unverifiedJobId = (int) $pdo->lastInsertId();

    // Worker uploads a Resume and Portfolio through the real multipart HTTP endpoint.
    $cookies['worker'] = e2e_login($workerUsername, $password);
    $profilePage = e2e_request(E2E_BASE_URL . '/worker/editprofiles.php', $cookies['worker']);
    $uploadResponse = e2e_request(E2E_BASE_URL . '/worker/editprofiles.php', $cookies['worker'], [
        'csrf_token' => e2e_csrf($profilePage['body']),
        'username' => $workerUsername,
        'first_name' => 'Worker',
        'last_name' => 'Upload Test',
        'phone' => '0800000000',
        'headline' => 'Upload access fixture',
        'introduce' => 'Testing resume and portfolio access.',
        'preferred_work_mode' => 'any',
        'profile_visibility' => 'searchable',
        'resume_file' => new CURLFile($fixturePdf, 'application/pdf', 'resume.pdf'),
        'portfolio_file' => new CURLFile($fixturePdf, 'application/pdf', 'portfolio.pdf'),
    ]);
    e2e_assert($uploadResponse['status'] === 200 && str_contains($uploadResponse['body'], 'บันทึกข้อมูลโปรไฟล์แล้ว'), 'Valid Resume/Portfolio upload failed');

    $paths = $pdo->prepare('SELECT resume_file_path,portfolio_file_path FROM worker_profiles WHERE user_id=?');
    $paths->execute([$workerId]);
    $uploaded = $paths->fetch();
    $resumePath = $uploaded['resume_file_path'] ?? null;
    $portfolioPath = $uploaded['portfolio_file_path'] ?? null;
    e2e_assert((bool) $resumePath && (bool) $portfolioPath, 'Uploaded file paths were not saved');
    e2e_assert(is_file(APP_ROOT . '/' . $resumePath) && is_file(APP_ROOT . '/' . $portfolioPath), 'Uploaded files were not stored');

    // Invalid extensions must be rejected and must not overwrite valid saved files.
    $profilePage = e2e_request(E2E_BASE_URL . '/worker/editprofiles.php', $cookies['worker']);
    $invalidUpload = e2e_request(E2E_BASE_URL . '/worker/editprofiles.php', $cookies['worker'], [
        'csrf_token' => e2e_csrf($profilePage['body']),
        'username' => $workerUsername,
        'first_name' => 'Worker',
        'last_name' => 'Upload Test',
        'preferred_work_mode' => 'any',
        'profile_visibility' => 'searchable',
        'resume_file' => new CURLFile($fixtureTxt, 'text/plain', 'not-allowed.txt'),
    ]);
    e2e_assert($invalidUpload['status'] === 200 && str_contains($invalidUpload['body'], 'ชนิดไฟล์ไม่ถูกต้อง'), 'Invalid upload extension was accepted');
    $profilePage = e2e_request(E2E_BASE_URL . '/worker/editprofiles.php', $cookies['worker']);
    $spoofedPdf = e2e_request(E2E_BASE_URL . '/worker/editprofiles.php', $cookies['worker'], [
        'csrf_token' => e2e_csrf($profilePage['body']),
        'username' => $workerUsername,
        'first_name' => 'Worker',
        'last_name' => 'Upload Test',
        'preferred_work_mode' => 'any',
        'profile_visibility' => 'searchable',
        'resume_file' => new CURLFile($fixtureTxt, 'text/plain', 'spoofed.pdf'),
    ]);
    e2e_assert($spoofedPdf['status'] === 200 && str_contains($spoofedPdf['body'], 'เนื้อหาไฟล์ไม่ตรงกับชนิดไฟล์'), 'Spoofed PDF content was accepted');
    $paths->execute([$workerId]);
    $afterRejectedUploads = $paths->fetch();
    e2e_assert($afterRejectedUploads['resume_file_path'] === $resumePath && $afterRejectedUploads['portfolio_file_path'] === $portfolioPath, 'Rejected upload overwrote an existing document');

    // Owner is allowed; an anonymous visitor is redirected to login.
    e2e_assert(e2e_is_pdf_response(e2e_request(E2E_BASE_URL . '/download.php?type=profile_resume&id=' . $workerId, $cookies['worker'])), 'Worker cannot open own Resume');
    e2e_assert(e2e_is_pdf_response(e2e_request(E2E_BASE_URL . '/download.php?type=profile_portfolio&id=' . $workerId, $cookies['worker'])), 'Worker cannot open own Portfolio');
    e2e_assert(e2e_request(E2E_BASE_URL . '/download.php?type=profile_resume&id=' . $workerId, null, null, false)['status'] === 302, 'Anonymous Resume access was not redirected');
    $cookies['admin'] = e2e_login($adminUsername, $password);
    e2e_assert(e2e_is_pdf_response(e2e_request(E2E_BASE_URL . '/download.php?type=profile_resume&id=' . $workerId, $cookies['admin'])), 'Admin cannot open worker Resume');

    // Only a verified employer who owns an open job can use Candidate access.
    $cookies['verified'] = e2e_login($verifiedEmployerUsername, $password);
    $candidateResume = E2E_BASE_URL . '/download.php?type=candidate_resume&worker=' . $workerId . '&job=' . $jobId;
    $candidatePortfolio = E2E_BASE_URL . '/download.php?type=candidate_portfolio&worker=' . $workerId . '&job=' . $jobId;
    e2e_assert(e2e_is_pdf_response(e2e_request($candidateResume, $cookies['verified'])), 'Verified employer cannot open candidate Resume');
    e2e_assert(e2e_is_pdf_response(e2e_request($candidatePortfolio, $cookies['verified'])), 'Verified employer cannot open candidate Portfolio');
    e2e_assert(e2e_request(E2E_BASE_URL . '/' . $resumePath, null, null, false)['status'] === 403, 'Resume uploads are directly web-accessible');

    $cookies['unverified'] = e2e_login($unverifiedEmployerUsername, $password);
    $unverifiedUrl = E2E_BASE_URL . '/download.php?type=candidate_resume&worker=' . $workerId . '&job=' . $unverifiedJobId;
    e2e_assert(e2e_request($unverifiedUrl, $cookies['unverified'])['status'] === 404, 'Unverified employer can open candidate Resume');

    $cookies['other'] = e2e_login($otherEmployerUsername, $password);
    e2e_assert(e2e_request($candidateResume, $cookies['other'])['status'] === 404, 'Other employer can open a non-owned job candidate Resume');
    e2e_assert(e2e_request(E2E_BASE_URL . '/download.php?type=profile_resume&id=' . $workerId, $cookies['verified'])['status'] === 404, 'Employer bypassed Candidate access with profile endpoint');

    // After the worker hides their profile, candidate discovery files are blocked.
    $pdo->prepare("UPDATE worker_profiles SET profile_visibility='application_only' WHERE user_id=?")->execute([$workerId]);
    e2e_assert(e2e_request($candidateResume, $cookies['verified'])['status'] === 404, 'Hidden profile Resume is still available through Candidate access');

    // An application is explicit consent: its worker and the job owner can still open its document.
    $pdo->prepare('INSERT INTO applications (job_id,worker_user_id,resume_file_path,application_status) VALUES (?,?,?,\'submitted\')')
        ->execute([$jobId, $workerId, $resumePath]);
    $applicationId = (int) $pdo->lastInsertId();
    $applicationResume = E2E_BASE_URL . '/download.php?type=application_resume&id=' . $applicationId;
    $applicationPortfolio = E2E_BASE_URL . '/download.php?type=application_portfolio&id=' . $applicationId;
    e2e_assert(e2e_is_pdf_response(e2e_request($applicationResume, $cookies['worker'])), 'Worker cannot open Resume attached to own application');
    e2e_assert(e2e_is_pdf_response(e2e_request($applicationResume, $cookies['verified'])), 'Job owner cannot open applicant Resume');
    e2e_assert(e2e_is_pdf_response(e2e_request($applicationPortfolio, $cookies['verified'])), 'Job owner cannot open applicant Portfolio');
    e2e_assert(e2e_is_pdf_response(e2e_request($applicationResume, $cookies['admin'])), 'Admin cannot open applicant Resume');
    e2e_assert(e2e_request($applicationResume, $cookies['other'])['status'] === 404, 'Other employer can open applicant Resume');

    echo "upload and document access e2e smoke test: PASS\n";
} finally {
    foreach (array_reverse([$workerId, $verifiedEmployerId, $unverifiedEmployerId, $otherEmployerId, $adminId]) as $userId) {
        if ($userId > 0) $pdo->prepare('DELETE FROM users WHERE user_id=?')->execute([$userId]);
    }
    foreach ([$resumePath, $portfolioPath] as $path) {
        $absolute = $path ? realpath(APP_ROOT . '/' . ltrim($path, '/\\')) : false;
        $uploads = realpath(APP_ROOT . '/uploads');
        if ($absolute && $uploads && str_starts_with(strtolower($absolute), strtolower($uploads . DIRECTORY_SEPARATOR))) unlink($absolute);
    }
    foreach (array_merge([$fixturePdf, $fixtureTxt], $cookies) as $temporaryFile) {
        if (is_string($temporaryFile) && is_file($temporaryFile)) unlink($temporaryFile);
    }
}
