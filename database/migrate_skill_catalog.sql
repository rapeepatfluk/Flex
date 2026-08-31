-- FLEXJOB skill catalog migration
-- Run once on an existing db_flexjob database.
USE db_flexjob;

CREATE TABLE IF NOT EXISTS skill_categories (
  skill_category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL,
  category_slug VARCHAR(100) NOT NULL UNIQUE,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE skills
  ADD COLUMN skill_category_id INT UNSIGNED NULL AFTER skill_id,
  ADD COLUMN is_custom TINYINT(1) NOT NULL DEFAULT 0 AFTER skill_name,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_custom,
  ADD INDEX idx_skill_catalog (skill_category_id, is_active),
  ADD CONSTRAINT fk_skill_category FOREIGN KEY (skill_category_id) REFERENCES skill_categories(skill_category_id) ON DELETE SET NULL;

INSERT INTO skill_categories (category_name, category_slug, sort_order) VALUES
  ('งานบริการและร้านค้า', 'service-retail', 10),
  ('งานอีเวนต์', 'event', 20),
  ('ขายและการตลาด', 'sales-marketing', 30),
  ('ครีเอทีฟและดิจิทัล', 'creative-digital', 40),
  ('งานสำนักงาน', 'office', 50),
  ('เทคโนโลยีและการออกแบบ', 'technology-design', 60),
  ('ขนส่งและงานทั่วไป', 'logistics-general', 70)
ON DUPLICATE KEY UPDATE category_name=VALUES(category_name), sort_order=VALUES(sort_order), is_active=1;

INSERT INTO skills (skill_name, skill_category_id, is_custom, is_active)
SELECT seed.skill_name, category.skill_category_id, 0, 1
FROM (
  SELECT 'บริการลูกค้า' AS skill_name, 'service-retail' AS category_slug UNION ALL SELECT 'รับออเดอร์', 'service-retail' UNION ALL SELECT 'แคชเชียร์', 'service-retail' UNION ALL SELECT 'ชงกาแฟ', 'service-retail' UNION ALL SELECT 'จัดเรียงสินค้า', 'service-retail' UNION ALL SELECT 'ใช้ระบบ POS', 'service-retail' UNION ALL SELECT 'ทำอาหาร', 'service-retail' UNION ALL SELECT 'ตอบแชตลูกค้า', 'service-retail' UNION ALL
  SELECT 'ลงทะเบียนหน้างาน', 'event' UNION ALL SELECT 'ดูแลบูธ', 'event' UNION ALL SELECT 'ประสานงาน', 'event' UNION ALL SELECT 'จัดสถานที่', 'event' UNION ALL SELECT 'MC / พิธีกร', 'event' UNION ALL SELECT 'ดูแลเครื่องเสียง', 'event' UNION ALL SELECT 'ถ่ายภาพหน้างาน', 'event' UNION ALL SELECT 'ควบคุมคิว', 'event' UNION ALL
  SELECT 'การขาย', 'sales-marketing' UNION ALL SELECT 'ปิดการขาย', 'sales-marketing' UNION ALL SELECT 'Live ขายสินค้า', 'sales-marketing' UNION ALL SELECT 'Canva', 'sales-marketing' UNION ALL SELECT 'ดูแลโซเชียลมีเดีย', 'sales-marketing' UNION ALL SELECT 'ยิงโฆษณาออนไลน์', 'sales-marketing' UNION ALL SELECT 'ทำคอนเทนต์', 'sales-marketing' UNION ALL SELECT 'วิเคราะห์ลูกค้า', 'sales-marketing' UNION ALL
  SELECT 'Photoshop', 'creative-digital' UNION ALL SELECT 'Illustrator', 'creative-digital' UNION ALL SELECT 'ตัดต่อวิดีโอ', 'creative-digital' UNION ALL SELECT 'ถ่ายภาพ', 'creative-digital' UNION ALL SELECT 'เขียนคอนเทนต์', 'creative-digital' UNION ALL SELECT 'Motion Graphic', 'creative-digital' UNION ALL SELECT '3D Design', 'creative-digital' UNION ALL SELECT 'CapCut', 'creative-digital' UNION ALL
  SELECT 'Microsoft Excel', 'office' UNION ALL SELECT 'Google Workspace', 'office' UNION ALL SELECT 'คีย์ข้อมูล', 'office' UNION ALL SELECT 'จัดเอกสาร', 'office' UNION ALL SELECT 'พิมพ์เอกสาร', 'office' UNION ALL SELECT 'จัดตารางนัดหมาย', 'office' UNION ALL SELECT 'รับโทรศัพท์', 'office' UNION ALL SELECT 'ธุรการ', 'office' UNION ALL
  SELECT 'HTML / CSS', 'technology-design' UNION ALL SELECT 'JavaScript', 'technology-design' UNION ALL SELECT 'PHP', 'technology-design' UNION ALL SELECT 'MySQL / Database', 'technology-design' UNION ALL SELECT 'Front-end Development', 'technology-design' UNION ALL SELECT 'Back-end Development', 'technology-design' UNION ALL SELECT 'Full-stack Development', 'technology-design' UNION ALL SELECT 'WordPress', 'technology-design' UNION ALL SELECT 'UI / UX Design', 'technology-design' UNION ALL SELECT 'Figma', 'technology-design' UNION ALL SELECT 'Web Design', 'technology-design' UNION ALL SELECT 'Graphic Design', 'technology-design' UNION ALL
  SELECT 'ขับรถยนต์', 'logistics-general' UNION ALL SELECT 'ขับมอเตอร์ไซค์', 'logistics-general' UNION ALL SELECT 'แพ็กสินค้า', 'logistics-general' UNION ALL SELECT 'ยกของ', 'logistics-general' UNION ALL SELECT 'ใช้เครื่องมือช่าง', 'logistics-general' UNION ALL SELECT 'ติดตั้งอุปกรณ์', 'logistics-general' UNION ALL SELECT 'ตรวจนับสินค้า', 'logistics-general' UNION ALL SELECT 'จัดส่งสินค้า', 'logistics-general'
) AS seed
JOIN skill_categories AS category ON category.category_slug = seed.category_slug
ON DUPLICATE KEY UPDATE skill_category_id = COALESCE(skills.skill_category_id, VALUES(skill_category_id)), is_active = 1;
