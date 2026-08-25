<?php require_once __DIR__ . '/config/config.php';
if (user() && ($_GET['scroll'] ?? '') !== 'how') redirect(dashboard_path(user()['role']));
$jobs = db()->query("SELECT j.job_id AS id,j.job_title AS title,jc.category_slug AS job_type,j.job_description AS description,j.work_location AS location,j.work_schedule AS work_date,j.pay_amount,j.pay_unit,j.open_positions AS positions,ep.company_name,ep.company_logo_path AS company_logo,(SELECT ed.document_status='approved' FROM employer_documents ed WHERE ed.employer_user_id=j.employer_user_id ORDER BY ed.submitted_at DESC,ed.employer_document_id DESC LIMIT 1) is_verified,(SELECT ji.image_file_path FROM job_images ji WHERE ji.job_id=j.job_id ORDER BY ji.display_order LIMIT 1) cover_image FROM jobs j JOIN employer_profiles ep ON ep.user_id=j.employer_user_id JOIN job_categories jc ON jc.job_category_id=j.job_category_id WHERE j.job_status='published' AND (j.application_deadline IS NULL OR j.application_deadline>=CURDATE()) ORDER BY j.created_at DESC LIMIT 10")->fetchAll();
$pageTitle = 'FLEXJOB | งานที่ยืดหยุ่นสำหรับคุณ';
$pageStyles = ['index', 'index-how'];
require __DIR__ . '/partials/header.php'; ?>

<main>
    <section class="hero">
        <div>
            <p class="eyebrow">FLEXIBLE WORK, REAL OPPORTUNITY</p>
            <h1>งานที่ใช่<br>ในเวลาที่<span>ยืดหยุ่น</span></h1>
            <p class="lead">ค้นหางานพาร์ทไทม์ งานอีเวนต์ และฟรีแลนซ์จากผู้ว่าจ้างที่ผ่านการตรวจสอบแล้ว</p>
            <form class="search-bar" action="<?= BASE_URL ?>/jobs.php" method="get"><input name="q" placeholder="ค้นหาตำแหน่ง, ทักษะ หรือชื่อบริษัท"><input name="province" placeholder="จังหวัด หรือทำงานออนไลน์"><button class="btn btn-primary">ค้นหางาน</button></form>
            <p class="popular">ค้นหายอดนิยม: <a href="<?= BASE_URL ?>/jobs.php?type=event">Staff Event</a><a href="<?= BASE_URL ?>/jobs.php?type=part_time">งานพาร์ทไทม์</a><a href="<?= BASE_URL ?>/jobs.php?type=freelance">กราฟิก</a></p>
        </div>
        <div class="hero-visual">
            <div class="float-card top-card"><b>✦ Marketing Crew</b><small>งานอีเวนต์ · 800 บาท/วัน</small></div>
            <div class="hero-person">
                <div class="person-face"></div>
                <div class="person-shirt">FLEXJOB</div>
            </div>
            <div class="float-card bottom-card"><b>✓ ได้งานแล้ว!</b><small>เริ่มงาน 24 ส.ค.</small></div>
            <div class="hero-badge">งานที่เหมาะกับคุณ<br><strong>1,240+</strong> ตำแหน่ง</div>
        </div>
    </section>
    <section class="worker-cta">
        <div>
            <p class="eyebrow">START YOUR JOURNEY</p>
            <h2>พร้อมเริ่มงานใหม่แล้วหรือยัง?</h2>
            <p>สร้างโปรไฟล์ อัปโหลด Resume และรับโอกาสงานที่เหมาะกับคุณ</p>
        </div><a class="btn btn-light text-primary fw-semibold px-4 py-2" href="<?= BASE_URL ?>/auth/register.php">สร้างโปรไฟล์ฟรี →</a>
    </section>
    <aside class="ad-banner" aria-label="โฆษณา FLEXJOB">
        <img src="<?= BASE_URL ?>/assets/images/flexjob-advertisement-v1.png" alt="FLEXJOB หางานง่าย จบในที่เดียว">
    </aside>
    <section class="section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">EXPLORE OPPORTUNITIES</p>
                <h2>เลือกงานที่เข้ากับไลฟ์สไตล์คุณ</h2>
            </div><a class="text-link green" href="<?= BASE_URL ?>/jobs.php">ดูงานทั้งหมด →</a>
        </div>
        <div class="category-grid" id="jobCategories"><button type="button" class="category-card active" data-type="all"><i>⌘</i><b>งานทั้งหมด</b><small>ทุกโอกาสที่เปิดรับ</small></button><button type="button" class="category-card" data-type="part_time"><i>◷</i><b>พาร์ทไทม์</b><small>ทำงานตามกะ</small></button><button type="button" class="category-card" data-type="event"><i>✦</i><b>งานอีเวนต์</b><small>สนุก ได้ประสบการณ์</small></button><button type="button" class="category-card" data-type="freelance"><i>⌁</i><b>ฟรีแลนซ์</b><small>ทำงานในแบบคุณ</small></button></div>
    </section>
    <section class="section soft home-recommendations">
        <div class="section-heading">
            <div>
                <p class="eyebrow">JUST POSTED</p>
                <h2>งานเปิดรับล่าสุด</h2>
            </div>
            <div class="home-job-controls"><a class="filter-link" href="<?= BASE_URL ?>/jobs.php">☷ ตัวกรอง</a>
                <form action="<?= BASE_URL ?>/jobs.php" method="get"><select name="sort" onchange="this.form.submit()">
                        <option value="new">ล่าสุด</option>
                        <option value="pay">ค่าจ้างสูงสุด</option>
                    </select></form>
            </div>
        </div>
        <div class="home-jobs-layout">
            <aside class="home-filter-panel"><b>ตัวกรองงาน</b><label>รูปแบบงาน<select id="homeTypeFilter">
                        <option value="all">ทั้งหมด</option>
                        <option value="part_time">พาร์ทไทม์</option>
                        <option value="event">งานอีเวนต์</option>
                        <option value="freelance">ฟรีแลนซ์</option>
                    </select></label><button type="button" id="clearHomeFilters">ล้างตัวกรอง</button></aside>
            <div class="home-job-list" id="homeJobList"><?php foreach ($jobs as $job): $icon = $job['job_type'] === 'event' ? '✦' : ($job['job_type'] === 'freelance' ? '⌁' : '◷'); ?><article class="home-job-row" data-job-type="<?= e($job['job_type']) ?>"><?php if ($job['cover_image']): ?><img class="home-job-image" src="<?= BASE_URL . '/' . e($job['cover_image']) ?>" alt="<?= e($job['title']) ?>"><?php else: ?><div class="home-job-image home-job-icon <?= e($job['job_type']) ?>"><?= $icon ?></div><?php endif ?><div class="home-job-content">
                            <?php if ($job['is_verified']): ?><p class="home-verified">● ผู้ว่าจ้างยืนยันแล้ว</p><?php endif ?>
                            <h3><?= e($job['title']) ?></h3>
                            <p class="home-company"><?php if ($job['company_logo']): ?><img class="company-logo" src="<?= BASE_URL . '/' . e($job['company_logo']) ?>" alt="โลโก้ <?= e($job['company_name']) ?>"><?php endif; ?><?= e($job['company_name']) ?></p>
                            <div class="home-job-tags"><span><?= job_type($job['job_type']) ?></span><span><?= e($job['positions']) ?> อัตรา</span></div>
                            <p class="home-job-location">⌖ <?= e($job['location']) ?></p>
                            <div class="home-job-footer">
                                <div><small>◷ <?= e($job['work_date']) ?></small><strong><?= pay_text($job) ?></strong></div><a class="btn btn-primary btn-sm home-job-button" href="<?= BASE_URL ?>/job.php?id=<?= $job['id'] ?>">ดูงานนี้</a>
                            </div>
                        </div>
                    </article><?php endforeach ?><p class="empty home-no-results" hidden>ไม่พบงานในหมวดนี้</p><?php if (!$jobs): ?><p class="empty">ยังไม่มีงานแนะนำในขณะนี้</p><?php endif ?></div>
        </div>
    </section>
    <section class="how-section" id="how">
        <div class="how-visual">
        <div class="phone"> <b>FLEXJOB</b>
            <h3>สวัสดี 👋</h3>
            <p>งานใหม่สำหรับคุณ</p>
            <div>✦ <b>Event Staff</b><small>฿900 / วัน</small></div>
            <div>⌁ <b>Content Creator</b><small>฿1,500 / งาน</small></div>
        </div>
        </div>
        <div class="how-content">
            <p class="eyebrow">HOW FLEXJOB WORKS</p>
            <h2>หางานง่าย<br>จบในไม่กี่ขั้นตอน</h2>
            <ol>
                <li><span>01</span>
                    <div><b>สร้างโปรไฟล์ของคุณ</b>
                        <p>เพิ่มข้อมูล ทักษะ และ Resume เพื่อให้ผู้ว่าจ้างรู้จักคุณ</p>
                    </div>
                </li>
                <li><span>02</span>
                    <div><b>เลือกงานที่สนใจ</b>
                        <p>ค้นหาและกรองงานตามเวลา พื้นที่ และค่าจ้าง</p>
                    </div>
                </li>
                <li><span>03</span>
                    <div><b>สมัคร แล้วเริ่มงาน</b>
                        <p>ติดตามผลสมัครและรับข้อเสนอผ่านหน้าเดียว</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

</main>
<div class="modal fade advertisement-modal" id="advertisementModal" tabindex="-1" aria-labelledby="advertisementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 bg-transparent">
            <h2 class="visually-hidden" id="advertisementModalLabel">โฆษณา FLEXJOB</h2>
            <button class="btn-close btn-close-white advertisement-close" type="button" data-bs-dismiss="modal" aria-label="ปิดโฆษณา"></button>
            <img class="advertisement-image" src="<?= BASE_URL ?>/assets/images/flexjob-ad-banner-v1.png" alt="FLEXJOB หางานง่าย จบในที่เดียว">
            <div class="form-check advertisement-option">
                <input class="form-check-input" id="hideAdvertisement" type="checkbox">
                <label class="form-check-label" for="hideAdvertisement">ไม่ต้องแสดงอีก</label>
            </div>
        </div>
    </div>
</div>
<script>
    const categoryButtons = document.querySelectorAll('#jobCategories [data-type]');
    const typeSelect = document.querySelector('#homeTypeFilter');
    const jobRows = document.querySelectorAll('#homeJobList .home-job-row');
    const noResults = document.querySelector('.home-no-results');

    function filterHomeJobs(type) {
        let count = 0;
        jobRows.forEach(row => {
            const visible = type === 'all' || row.dataset.jobType === type;
            row.hidden = !visible;
            if (visible) count++;
        });
        noResults.hidden = count !== 0;
        categoryButtons.forEach(button => button.classList.toggle('active', button.dataset.type === type));
        typeSelect.value = type;
        document.querySelector('.home-recommendations').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
    categoryButtons.forEach(button => button.addEventListener('click', () => filterHomeJobs(button.dataset.type)));
    typeSelect.addEventListener('change', () => filterHomeJobs(typeSelect.value));
    document.querySelector('#clearHomeFilters').addEventListener('click', () => filterHomeJobs('all'));

    document.addEventListener('DOMContentLoaded', () => {
        const modalElement = document.querySelector('#advertisementModal');
        const hideCheckbox = document.querySelector('#hideAdvertisement');

        if (!localStorage.getItem('flexjob-hide-advertisement')) {
            new bootstrap.Modal(modalElement).show();
        }

        modalElement.addEventListener('hide.bs.modal', () => {
            if (hideCheckbox.checked) {
                localStorage.setItem('flexjob-hide-advertisement', '1');
            }
        });
    });
</script><?php require __DIR__ . '/partials/footer.php'; ?>
