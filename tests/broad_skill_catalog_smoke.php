<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$pdo = db();
$expectedCounts = [
    'service-retail' => 5,
    'event' => 7,
    'sales-marketing' => 7,
    'creative-digital' => 7,
    'office' => 6,
    'technology-design' => 6,
    'logistics-general' => 8,
];

$statement = $pdo->query("SELECT category.category_slug,COUNT(skill.skill_id) total
    FROM skill_categories category
    LEFT JOIN skills skill ON skill.skill_category_id=category.skill_category_id AND skill.is_active=1 AND skill.is_custom=0
    WHERE category.is_active=1
    GROUP BY category.skill_category_id");
$actualCounts = [];
foreach ($statement->fetchAll() as $row) $actualCounts[$row['category_slug']] = (int) $row['total'];

foreach ($expectedCounts as $slug => $expected) {
    if (($actualCounts[$slug] ?? null) !== $expected) {
        throw new RuntimeException("Unexpected broad skill count for {$slug}");
    }
}

$required = ['รับสายและประสานงาน','พัฒนาเว็บไซต์','บริการและดูแลลูกค้า','งานช่างและซ่อมบำรุง'];
$placeholders = implode(',',array_fill(0,count($required),'?'));
$active = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE is_active=1 AND skill_name IN ({$placeholders})");
$active->execute($required);
if ((int) $active->fetchColumn() !== count($required)) throw new RuntimeException('Required broad skills are missing');

$removed = ['ระบบอัตโนมัติและเครื่องมือ No-code','ความปลอดภัยทางไซเบอร์เบื้องต้น','แก้ไขปัญหาหน้างาน','บันทึกภาพงานอีเวนต์','นัดหมายและประสานงาน'];
$placeholders = implode(',',array_fill(0,count($removed),'?'));
$removedStatement = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE is_active=1 AND skill_name IN ({$placeholders})");
$removedStatement->execute($removed);
if ((int) $removedStatement->fetchColumn() !== 0) throw new RuntimeException('A removed broad skill is still active');

$legacyLinks = (int) $pdo->query("SELECT
    (SELECT COUNT(*) FROM worker_skills ws JOIN skills s ON s.skill_id=ws.skill_id WHERE s.is_custom=0 AND s.is_active=0)
  + (SELECT COUNT(*) FROM job_skills js JOIN skills s ON s.skill_id=js.skill_id WHERE s.is_custom=0 AND s.is_active=0)")->fetchColumn();
if ($legacyLinks !== 0) throw new RuntimeException('Legacy skill links were not consolidated');

$catalogSkillIds = [];
foreach (matching_skill_catalog($pdo) as $category) {
    foreach ($category['skills'] as $skill) $catalogSkillIds[] = (int) $skill['id'];
}
if ($catalogSkillIds) {
    $placeholders = implode(',',array_fill(0,count($catalogSkillIds),'?'));
    $customInCatalog = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE is_custom=1 AND skill_id IN ({$placeholders})");
    $customInCatalog->execute($catalogSkillIds);
    if ((int) $customInCatalog->fetchColumn() !== 0) throw new RuntimeException('A custom skill leaked into the shared catalog');
}

echo "broad skill catalog smoke test: PASS\n";
