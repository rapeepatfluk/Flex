<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_login('admin');

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $documentId = (int) ($_POST['employer_document_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $note = trim($_POST['review_note'] ?? '');
        if (!in_array($status, ['approved', 'rejected', 'resubmit'], true)) throw new RuntimeException('สถานะเอกสารไม่ถูกต้อง');

        $ownerStmt = $pdo->prepare('SELECT employer_user_id FROM employer_documents WHERE employer_document_id=?');
        $ownerStmt->execute([$documentId]);
        $employerId = (int) $ownerStmt->fetchColumn();
        if (!$employerId) throw new RuntimeException('ไม่พบเอกสารผู้ว่าจ้าง');

        $pdo->prepare('UPDATE employer_documents SET document_status=?, review_note=?, reviewed_by_user_id=?, reviewed_at=NOW() WHERE employer_document_id=?')
            ->execute([$status, $note ?: null, user()['id'], $documentId]);
        $statusText = ['approved' => 'ผ่านการตรวจสอบ', 'rejected' => 'ไม่ผ่านการตรวจสอบ', 'resubmit' => 'ต้องส่งเอกสารเพิ่มเติม'][$status];
        admin_notify_user($pdo, $employerId, 'ผลการตรวจเอกสารผู้ว่าจ้าง', 'เอกสารของคุณ' . $statusText . ($note ? ' — ' . $note : ''));
        flash('success', 'บันทึกผลการตรวจเอกสารและส่งการแจ้งเตือนแล้ว');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('admin/documents.php');
}

$query = trim($_GET['q'] ?? '');
$sql = "SELECT ed.employer_document_id, ed.employer_user_id, ed.document_file_path, ed.document_status, ed.review_note, ed.submitted_at, ep.company_name, CONCAT(u.first_name, ' ', u.last_name) AS name, u.email FROM employer_documents ed JOIN users u ON u.user_id=ed.employer_user_id JOIN employer_profiles ep ON ep.user_id=ed.employer_user_id WHERE ed.document_status IN ('pending', 'resubmit')";
$params = [];
if ($query !== '') {
    $sql .= ' AND (ep.company_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $term = '%' . $query . '%';
    $params = [$term, $term, $term, $term];
}
$sql .= ' ORDER BY ed.submitted_at DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$documents = $statement->fetchAll();

$pageTitle = 'ตรวจเอกสารผู้ว่าจ้าง | FLEXJOB';
require APP_ROOT . '/partials/header.php';
?>

<main class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow">VERIFICATION</p>
            <h1 class="h2 mb-1">เอกสารผู้ว่าจ้างที่ต้องตรวจสอบ</h1>
            <p class="text-secondary mb-0">อนุมัติ ไม่ผ่าน หรือขอเอกสารเพิ่มเติม</p>
        </div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/dashboard.php">กลับหน้า Admin</a>
    </div>
    <form class="row g-2 mb-4" method="get">
        <div class="col-md-6"><input class="form-control" name="q" value="<?= e($query) ?>" placeholder="ค้นหาชื่อบริษัท ชื่อผู้ว่าจ้าง หรืออีเมล"></div>
        <div class="col-auto"><button class="btn btn-success" type="submit">ค้นหา</button></div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <?php foreach ($documents as $document): ?><article class="border-bottom py-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <h2 class="h5 mb-1"><a class="link-primary" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $document['employer_user_id'] ?>"><?= e($document['company_name']) ?></a></h2>
                            <p class="text-secondary small mb-2"><?= e($document['name']) ?> · <?= e($document['email']) ?> · ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($document['submitted_at'])) ?></p><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $document['employer_user_id'] ?>">ดูข้อมูลผู้ว่าจ้าง</a><?php if ($document['document_file_path']): ?> <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=employer_document&id=<?= $document['employer_document_id'] ?>">เปิดเอกสาร</a><?php endif; ?>
                        </div>
                        <div class="col-lg-6">
                            <form class="row g-2" method="post"><?= csrf_field() ?><input type="hidden" name="employer_document_id" value="<?= $document['employer_document_id'] ?>">
                                <div class="col-12"><input class="form-control" name="review_note" value="<?= e($document['review_note'] ?? '') ?>" placeholder="หมายเหตุถึงผู้ว่าจ้าง"></div>
                                <div class="col-sm"><select class="form-select" name="status">
                                        <option value="approved">อนุมัติ</option>
                                        <option value="resubmit">ขอเอกสารเพิ่ม</option>
                                        <option value="rejected">ไม่ผ่าน</option>
                                    </select></div>
                                <div class="col-sm-auto"><button class="btn btn-success w-100" type="submit">ยืนยันผล</button></div>
                            </form>
                        </div>
                    </div>
                </article><?php endforeach; ?>
            <?php if (!$documents): ?><p class="text-secondary mb-0">ไม่พบเอกสารที่รอตรวจสอบ</p><?php endif; ?>
        </div>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
