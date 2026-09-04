<?php
if (!function_exists('render_skill_selector')) {
    function render_skill_selector(
        string $selectorId,
        string $title,
        string $hint,
        array $categories,
        array $selectedIds,
        string $inputName,
        string $customInputName,
        bool $required = false
    ): void {
        $selectedIds = array_map('intval', $selectedIds);
        ?>
        <section class="skill-selector" id="<?= e($selectorId) ?>" data-skill-selector data-limit="20">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
                <div>
                    <h3 class="h6 mb-1"><?= e($title) ?><?= $required ? ' <span class="text-danger">*</span>' : '' ?></h3>
                    <p class="small text-secondary mb-0"><?= e($hint) ?></p>
                </div>
                <span class="badge text-bg-light border text-primary-emphasis"><b data-skill-count>0</b> / 20 รายการ</span>
            </div>

            <label class="visually-hidden" for="<?= e($selectorId) ?>Search">ค้นหาความสามารถ</label>
            <input class="form-control mb-3" id="<?= e($selectorId) ?>Search" type="search" data-skill-search placeholder="ค้นหาความสามารถ เช่น พัฒนาเว็บไซต์, บริการลูกค้า, งานอีเวนต์">

            <div class="nav nav-pills gap-2 flex-nowrap overflow-auto pb-2 mb-3" data-skill-tabs>
                <button class="nav-link active flex-shrink-0" type="button" data-skill-tab="all">ทั้งหมด</button>
                <?php foreach ($categories as $category): ?>
                    <button class="nav-link flex-shrink-0" type="button" data-skill-tab="<?= e($category['slug']) ?>"><?= e($category['name']) ?></button>
                <?php endforeach ?>
            </div>

            <div class="row g-3" data-skill-groups>
                <?php foreach ($categories as $category): ?>
                    <div class="col-12 skill-selector-group" data-skill-group="<?= e($category['slug']) ?>">
                        <div class="small fw-bold text-primary mb-2"><?= e($category['name']) ?></div>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-2">
                            <?php foreach ($category['skills'] as $skill): ?>
                                <div class="col skill-selector-option" data-skill-name="<?= e(mb_strtolower($skill['name'], 'UTF-8')) ?>">
                                    <label class="form-check border rounded-3 p-2 h-100 d-flex align-items-center gap-2 bg-white">
                                        <input class="form-check-input flex-shrink-0 m-0" type="checkbox" name="<?= e($inputName) ?>" value="<?= (int) $skill['id'] ?>" <?= in_array((int) $skill['id'], $selectedIds, true) ? 'checked' : '' ?>>
                                        <span class="form-check-label small fw-semibold"><?= e($skill['name']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="skill-selector-empty border rounded-3 p-3 text-center small text-secondary mt-3" data-skill-empty hidden>ไม่พบความสามารถจากคำค้นหา ลองพิมพ์ในช่อง “อื่น ๆ” ด้านล่างได้</div>
            <div class="mt-3 pt-3 border-top">
                <label class="form-label small fw-semibold mb-1" for="<?= e($selectorId) ?>Custom">อื่น ๆ <span class="text-secondary fw-normal">(พิมพ์เพิ่มเองได้)</span></label>
                <input class="form-control" id="<?= e($selectorId) ?>Custom" name="<?= e($customInputName) ?>" maxlength="300" placeholder="เช่น ตัดต่อวิดีโอ, ไลฟ์ขายสินค้า, งานฝีมือ — คั่นแต่ละรายการด้วยจุลภาค">
                <div class="form-text">ความสามารถที่พิมพ์เองจะถูกบันทึกและใช้จับคู่ได้เช่นเดียวกับรายการในระบบ</div>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('render_job_skill_assignment_selector')) {
    function render_job_skill_assignment_selector(string $selectorId, array $categories): void
    {
        ?>
        <section class="skill-selector job-skill-selector" id="<?= e($selectorId) ?>" data-skill-selector data-limit="20">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
                <div>
                    <p class="eyebrow mb-1">SKILL REQUIREMENTS</p>
                    <h2 class="h5 mb-1">ความสามารถที่ต้องการ</h2>
                    <p class="small text-secondary mb-0">เลือกความสามารถภาพรวมที่ผู้สมัครควรมี ระบบจะใช้ช่วยจับคู่ผู้สมัครกับประกาศนี้</p>
                </div>
                <span class="badge text-bg-light border text-primary-emphasis"><b data-skill-count>0</b> / 20 รายการ</span>
            </div>

            <label class="visually-hidden" for="<?= e($selectorId) ?>Search">ค้นหาความสามารถ</label>
            <input class="form-control mb-3" id="<?= e($selectorId) ?>Search" type="search" data-skill-search placeholder="ค้นหาความสามารถ เช่น พัฒนาเว็บไซต์, บริการลูกค้า, งานอีเวนต์">

            <div class="nav nav-pills gap-2 flex-nowrap overflow-auto pb-2 mb-3" data-skill-tabs>
                <button class="nav-link active flex-shrink-0" type="button" data-skill-tab="all">ทั้งหมด</button>
                <?php foreach ($categories as $category): ?>
                    <button class="nav-link flex-shrink-0" type="button" data-skill-tab="<?= e($category['slug']) ?>"><?= e($category['name']) ?></button>
                <?php endforeach ?>
            </div>

            <fieldset class="border-0 p-0 m-0">
                <legend class="visually-hidden">เลือกความสามารถที่จำเป็น</legend>
                <div class="row g-3" data-skill-groups>
                    <?php foreach ($categories as $category): ?>
                        <div class="col-12 skill-selector-group" data-skill-group="<?= e($category['slug']) ?>">
                            <div class="small fw-bold text-primary mb-2"><?= e($category['name']) ?></div>
                            <div class="row row-cols-1 row-cols-lg-2 g-2">
                                <?php foreach ($category['skills'] as $skill): ?>
                                    <div class="col skill-selector-option" data-skill-name="<?= e(mb_strtolower($skill['name'], 'UTF-8')) ?>">
                                        <div class="job-skill-choice border rounded-3 h-100 bg-white">
                                            <label class="form-check d-flex align-items-center gap-2 mb-0">
                                                <input class="form-check-input flex-shrink-0 m-0" type="checkbox" name="job_skill_ids[]" value="<?= (int) $skill['id'] ?>">
                                                <span class="form-check-label small fw-semibold"><?= e($skill['name']) ?></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </fieldset>

            <div class="skill-selector-empty border rounded-3 p-3 text-center small text-secondary mt-3" data-skill-empty hidden>ไม่พบความสามารถจากคำค้นหา ลองเพิ่มในช่อง “อื่น ๆ” ด้านล่างได้</div>
            <div class="mt-3 pt-3 border-top">
                <label class="form-label small fw-semibold mb-1" for="<?= e($selectorId) ?>Custom">อื่น ๆ <span class="text-secondary fw-normal">(คั่นแต่ละรายการด้วยจุลภาค)</span></label>
                <input class="form-control" id="<?= e($selectorId) ?>Custom" name="custom_job_skills" maxlength="300" placeholder="เช่น Blender, TikTok Shop, งานแกะสลัก">
                <div class="form-text">ความสามารถที่พิมพ์เองจะถูกบันทึกเป็นรายการจำเป็น และใช้จับคู่ได้เช่นเดียวกับรายการในระบบ</div>
            </div>
        </section>
        <?php
    }
}

if (!defined('FLEXJOB_SKILL_SELECTOR_SCRIPT')) {
    define('FLEXJOB_SKILL_SELECTOR_SCRIPT', true);
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-skill-selector]').forEach(selector => {
            const limit = Number(selector.dataset.limit || 20);
            const search = selector.querySelector('[data-skill-search]');
            const tabs = [...selector.querySelectorAll('[data-skill-tab]')];
            const groups = [...selector.querySelectorAll('[data-skill-group]')];
            const choices = [...selector.querySelectorAll('input[type="checkbox"]')];
            const count = selector.querySelector('[data-skill-count]');
            const empty = selector.querySelector('[data-skill-empty]');
            let category = 'all';

            const render = () => {
                const query = search.value.trim().toLocaleLowerCase();
                let visible = 0;
                groups.forEach(group => {
                    const categoryMatches = category === 'all' || group.dataset.skillGroup === category;
                    let groupVisible = 0;
                    group.querySelectorAll('.skill-selector-option').forEach(option => {
                        const matches = categoryMatches && (!query || option.dataset.skillName.includes(query));
                        option.hidden = !matches;
                        if (matches) groupVisible += 1;
                    });
                    group.hidden = groupVisible === 0;
                    visible += groupVisible;
                });
                empty.hidden = visible > 0;
                count.textContent = choices.filter(choice => choice.checked).length;
            };

            tabs.forEach(tab => tab.addEventListener('click', () => {
                category = tab.dataset.skillTab;
                tabs.forEach(item => item.classList.toggle('active', item === tab));
                render();
            }));
            choices.forEach(choice => choice.addEventListener('change', event => {
                if (choices.filter(item => item.checked).length > limit) {
                    event.currentTarget.checked = false;
                    alert(`เลือกความสามารถจากรายการได้สูงสุด ${limit} รายการ`);
                }
                render();
            }));
            search.addEventListener('input', render);
            render();
        });
    });
    </script>
    <?php
}
