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

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE employer_documents SET document_status=?, review_note=?, reviewed_by_user_id=?, reviewed_at=NOW() WHERE employer_document_id=?')
            ->execute([$status, $note ?: null, user()['id'], $documentId]);
        $statusText = ['approved' => 'ผ่านการตรวจสอบ', 'rejected' => 'ไม่ผ่านการตรวจสอบ', 'resubmit' => 'ต้องส่งเอกสารเพิ่มเติม'][$status];
        admin_notify_user($pdo, $employerId, 'ผลการตรวจเอกสารผู้ว่าจ้าง', 'เอกสารของคุณ' . $statusText . ($note ? ' — ' . $note : ''));
        $pdo->commit();
        flash('success', 'บันทึกผลการตรวจเอกสารและส่งการแจ้งเตือนแล้ว');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
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

$statusMeta = [
    'pending' => ['label' => 'รอตรวจสอบ', 'badge' => 'warning', 'icon' => '!', 'description' => 'เอกสารที่ส่งเข้ามาใหม่'],
    'resubmit' => ['label' => 'ส่งเอกสารเพิ่ม', 'badge' => 'info', 'icon' => '↻', 'description' => 'รอทบทวนเอกสารเพิ่มเติม'],
];

$pageTitle = 'ตรวจเอกสารผู้ว่าจ้าง | FLEXJOB';
$pageStyles = ['admin-documents'];
require APP_ROOT . '/partials/header.php';
?>

<main id="content" class="admin-documents" tabindex="-1">
    <div class="container">
        <header class="admin-documents-hero card border-0 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg">
                        <p class="admin-documents-eyebrow mb-2">VERIFICATION QUEUE</p>
                        <h1 class="display-6 mb-2">ตรวจเอกสารผู้ว่าจ้าง</h1>
                        <p class="admin-documents-lead mb-0">ตรวจข้อมูลบริษัท อนุมัติ หรือขอเอกสารเพิ่มเติม พร้อมแจ้งผลให้ผู้ว่าจ้างทราบทันที</p>
                    </div>
                    <div class="col-lg-auto">
                        <div class="admin-documents-summary">
                            <span class="admin-documents-summary-icon" aria-hidden="true">▤</span>
                            <div><strong><?= number_format(count($documents)) ?></strong><span>รายการที่ต้องดำเนินการ</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="card border-0 admin-documents-search mb-4" aria-label="ค้นหาเอกสาร">
            <div class="card-body p-3 p-lg-4">
                <form class="row g-2 align-items-center" method="get">
                    <div class="col-lg">
                        <label class="visually-hidden" for="document-search">ค้นหาชื่อบริษัท ชื่อผู้ว่าจ้าง หรืออีเมล</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">⌕</span>
                            <input class="form-control" id="document-search" name="q" value="<?= e($query) ?>" placeholder="ค้นหาชื่อบริษัท ชื่อผู้ว่าจ้าง หรืออีเมล">
                        </div>
                    </div>
                    <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit">ค้นหาเอกสาร</button></div>
                    <?php if ($query !== ''): ?><div class="col-sm-auto"><a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/admin/documents.php">ล้างการค้นหา</a></div><?php endif; ?>
                    <div class="col-lg-auto ms-lg-auto"><a class="btn btn-outline-primary w-100" href="<?= BASE_URL ?>/admin/dashboard.php">กลับหน้า Admin</a></div>
                </form>
            </div>
        </section>

        <section aria-labelledby="document-queue-heading">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-2 mb-3">
                <div>
                    <p class="admin-documents-eyebrow mb-1">PENDING REVIEW</p>
                    <h2 class="h3 mb-0" id="document-queue-heading"><?= $query !== '' ? 'ผลการค้นหาเอกสาร' : 'รายการที่ต้องตรวจสอบ' ?></h2>
                </div>
                <p class="small text-secondary mb-0">แสดง <?= number_format(count($documents)) ?> รายการ</p>
            </div>

            <?php if ($documents): ?>
                <div class="row g-3">
                    <?php foreach ($documents as $document):
                        $meta = $statusMeta[$document['document_status']] ?? $statusMeta['pending'];
                        $noteId = 'review-note-' . $document['employer_document_id'];
                        $statusId = 'review-status-' . $document['employer_document_id'];
                    ?>
                        <div class="col-12">
                            <article class="card border-0 admin-document-card h-100">
                                <div class="card-body p-4 p-lg-4">
                                    <div class="row g-4 align-items-start">
                                        <div class="col-xl-6">
                                            <div class="d-flex align-items-start gap-3">
                                                <span class="admin-document-icon admin-document-icon-<?= e($meta['badge']) ?>" aria-hidden="true"><?= e($meta['icon']) ?></span>
                                                <div class="min-w-0 flex-grow-1">
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                        <span class="badge rounded-pill text-bg-<?= e($meta['badge']) ?>"><?= e($meta['label']) ?></span>
                                                        <span class="small text-secondary"><?= e($meta['description']) ?></span>
                                                    </div>
                                                    <h3 class="h4 mb-1"><a class="link-dark text-decoration-none" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $document['employer_user_id'] ?>"><?= e($document['company_name']) ?></a></h3>
                                                    <p class="text-secondary mb-3"><?= e($document['name']) ?> <span aria-hidden="true">·</span> <?= e($document['email']) ?></p>
                                                    <dl class="admin-document-meta mb-3">
                                                        <div><dt>ส่งเอกสารเมื่อ</dt><dd><?= date('d/m/Y H:i', strtotime($document['submitted_at'])) ?></dd></div>
                                                        <div><dt>สถานะ</dt><dd><?= e($meta['label']) ?></dd></div>
                                                    </dl>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/admin/employer.php?id=<?= $document['employer_user_id'] ?>">ดูข้อมูลผู้ว่าจ้าง</a>
                                                        <?php if ($document['document_file_path']): ?><a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= BASE_URL ?>/download.php?type=employer_document&id=<?= $document['employer_document_id'] ?>">เปิดเอกสาร <span aria-hidden="true">↗</span></a><?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-6">
                                            <form class="admin-document-review" method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="employer_document_id" value="<?= $document['employer_document_id'] ?>">
                                                <div class="d-flex align-items-center gap-2 mb-3"><span class="admin-review-number" aria-hidden="true">2</span><div><h4 class="h6 mb-0">บันทึกผลการตรวจ</h4><p class="small text-secondary mb-0">ระบบจะแจ้งผลให้ผู้ว่าจ้างโดยอัตโนมัติ</p></div></div>
                                                <div class="row g-2">
                                                    <div class="col-md-7">
                                                        <label class="form-label small fw-semibold" for="<?= $statusId ?>">ผลการตรวจ</label>
                                                        <select class="form-select" id="<?= $statusId ?>" name="status">
                                                            <option value="approved">อนุมัติเอกสาร</option>
                                                            <option value="resubmit">ขอเอกสารเพิ่มเติม</option>
                                                            <option value="rejected">ไม่ผ่านการตรวจ</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">ยืนยันผลตรวจ</button></div>
                                                    <div class="col-12">
                                                        <label class="form-label small fw-semibold" for="<?= $noteId ?>">หมายเหตุถึงผู้ว่าจ้าง <span class="text-secondary fw-normal">(ไม่บังคับ)</span></label>
                                                        <textarea class="form-control" id="<?= $noteId ?>" name="review_note" rows="2" placeholder="เช่น กรุณาแนบหนังสือรับรองบริษัทฉบับล่าสุด"><?= e($document['review_note'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card border-0 admin-documents-empty">
                    <div class="card-body text-center p-5">
                        <span class="admin-documents-empty-icon" aria-hidden="true"><?= $query !== '' ? '⌕' : '✓' ?></span>
                        <h3 class="h4 mt-3"><?= $query !== '' ? 'ไม่พบเอกสารที่ตรงกับการค้นหา' : 'ไม่มีเอกสารที่รอตรวจสอบ' ?></h3>
                        <p class="text-secondary mb-4"><?= $query !== '' ? 'ลองเปลี่ยนคำค้นหา หรือกลับไปดูรายการเอกสารทั้งหมด' : 'รายการเอกสารได้รับการจัดการครบถ้วนในขณะนี้' ?></p>
                        <?php if ($query !== ''): ?><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/admin/documents.php">ดูเอกสารทั้งหมด</a><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require APP_ROOT . '/partials/footer.php'; ?>
