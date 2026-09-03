-- Consolidate detailed tools and technologies into broad, user-friendly capabilities.
-- Existing worker/job selections are migrated before legacy catalog rows are hidden.

UPDATE skill_categories
SET category_name='เทคโนโลยีและไอที'
WHERE category_slug='technology-design';

ALTER TABLE skills
  ADD COLUMN IF NOT EXISTS retired_at DATETIME NULL AFTER is_active;

CREATE TABLE IF NOT EXISTS skill_consolidation_map (
  legacy_skill_id INT UNSIGNED NOT NULL PRIMARY KEY,
  broad_skill_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_skill_map_legacy FOREIGN KEY (legacy_skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
  CONSTRAINT fk_skill_map_broad FOREIGN KEY (broad_skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
  INDEX idx_skill_map_broad (broad_skill_id)
) ENGINE=InnoDB;

CREATE TEMPORARY TABLE broad_skill_seed (
  skill_name VARCHAR(100) NOT NULL PRIMARY KEY,
  category_slug VARCHAR(100) NOT NULL
) ENGINE=Memory;

INSERT INTO broad_skill_seed (skill_name,category_slug) VALUES
  ('บริการและดูแลลูกค้า','service-retail'),
  ('ต้อนรับและให้ข้อมูล','service-retail'),
  ('งานอาหารและเครื่องดื่ม','service-retail'),
  ('รับออเดอร์และชำระเงิน','service-retail'),
  ('จัดการสินค้าในร้าน','service-retail'),

  ('จัดเตรียมสถานที่','event'),
  ('ต้อนรับและลงทะเบียน','event'),
  ('ประสานงานอีเวนต์','event'),
  ('ดูแลคิวและกิจกรรม','event'),
  ('ดูแลบูธและผู้ร่วมงาน','event'),
  ('พิธีกรและการนำเสนอ','event'),
  ('ดูแลสื่อและอุปกรณ์หน้างาน','event'),

  ('ขายและแนะนำสินค้า','sales-marketing'),
  ('เจรจาและปิดการขาย','sales-marketing'),
  ('ขายสินค้าออนไลน์และไลฟ์','sales-marketing'),
  ('ดูแลโซเชียลมีเดีย','sales-marketing'),
  ('สร้างคอนเทนต์การตลาด','sales-marketing'),
  ('โฆษณาและประชาสัมพันธ์','sales-marketing'),
  ('วิเคราะห์ลูกค้าและตลาด','sales-marketing'),

  ('ออกแบบกราฟิก','creative-digital'),
  ('ออกแบบเว็บไซต์และ UI/UX','creative-digital'),
  ('ถ่ายภาพ','creative-digital'),
  ('ถ่ายและตัดต่อวิดีโอ','creative-digital'),
  ('สร้างคอนเทนต์และเขียนเนื้อหา','creative-digital'),
  ('ภาพประกอบและงาน 3D','creative-digital'),
  ('ผลิตสื่อและงานนำเสนอ','creative-digital'),

  ('จัดทำและจัดเก็บเอกสาร','office'),
  ('คีย์และจัดการข้อมูล','office'),
  ('จัดทำตารางและรายงาน','office'),
  ('รับสายและประสานงาน','office'),
  ('งานธุรการและสนับสนุน','office'),
  ('บัญชีและการเงินเบื้องต้น','office'),

  ('พัฒนาเว็บไซต์','technology-design'),
  ('พัฒนาแอปพลิเคชันและซอฟต์แวร์','technology-design'),
  ('จัดการข้อมูลและฐานข้อมูล','technology-design'),
  ('ดูแลระบบและ IT Support','technology-design'),
  ('ดูแลเครือข่ายและเซิร์ฟเวอร์','technology-design'),
  ('ทดสอบระบบและซอฟต์แวร์','technology-design'),

  ('จัดส่งสินค้า','logistics-general'),
  ('งานคลังสินค้าและตรวจนับ','logistics-general'),
  ('แพ็กและจัดเตรียมสินค้า','logistics-general'),
  ('ขับรถและขนส่ง','logistics-general'),
  ('ติดตั้งและเคลื่อนย้ายอุปกรณ์','logistics-general'),
  ('งานช่างและซ่อมบำรุง','logistics-general'),
  ('งานใช้แรงและงานทั่วไป','logistics-general'),
  ('ดูแลสถานที่และความสะอาด','logistics-general');

INSERT INTO skills (skill_category_id,skill_name,is_custom,is_active,retired_at)
SELECT category.skill_category_id,seed.skill_name,0,1,NULL
FROM broad_skill_seed seed
JOIN skill_categories category ON category.category_slug=seed.category_slug
ON DUPLICATE KEY UPDATE
  skill_category_id=VALUES(skill_category_id),
  is_custom=0,
  is_active=1,
  retired_at=NULL;

CREATE TEMPORARY TABLE broad_skill_mapping_seed (
  legacy_name VARCHAR(100) NOT NULL PRIMARY KEY,
  broad_name VARCHAR(100) NOT NULL
) ENGINE=Memory;

INSERT INTO broad_skill_mapping_seed (legacy_name,broad_name) VALUES
  ('บริการลูกค้า','บริการและดูแลลูกค้า'),
  ('ตอบแชตลูกค้า','บริการและดูแลลูกค้า'),
  ('การบริการ','บริการและดูแลลูกค้า'),
  ('รับออเดอร์','รับออเดอร์และชำระเงิน'),
  ('แคชเชียร์','รับออเดอร์และชำระเงิน'),
  ('ใช้ระบบ POS','รับออเดอร์และชำระเงิน'),
  ('ชงกาแฟ','งานอาหารและเครื่องดื่ม'),
  ('ทำอาหาร','งานอาหารและเครื่องดื่ม'),
  ('จัดเรียงสินค้า','จัดการสินค้าในร้าน'),

  ('จัดสถานที่','จัดเตรียมสถานที่'),
  ('ลงทะเบียนหน้างาน','ต้อนรับและลงทะเบียน'),
  ('ประสานงาน','ประสานงานอีเวนต์'),
  ('ควบคุมคิว','ดูแลคิวและกิจกรรม'),
  ('ดูแลบูธ','ดูแลบูธและผู้ร่วมงาน'),
  ('MC / พิธีกร','พิธีกรและการนำเสนอ'),
  ('ดูแลเครื่องเสียง','ดูแลสื่อและอุปกรณ์หน้างาน'),
  ('ถ่ายภาพหน้างาน','ถ่ายภาพ'),

  ('การขาย','ขายและแนะนำสินค้า'),
  ('ปิดการขาย','เจรจาและปิดการขาย'),
  ('Live ขายสินค้า','ขายสินค้าออนไลน์และไลฟ์'),
  ('ทำคอนเทนต์','สร้างคอนเทนต์การตลาด'),
  ('ยิงโฆษณาออนไลน์','โฆษณาและประชาสัมพันธ์'),
  ('วิเคราะห์ลูกค้า','วิเคราะห์ลูกค้าและตลาด'),

  ('canva','ออกแบบกราฟิก'),
  ('Photoshop','ออกแบบกราฟิก'),
  ('Illustrator','ออกแบบกราฟิก'),
  ('Graphic Design','ออกแบบกราฟิก'),
  ('Figma','ออกแบบเว็บไซต์และ UI/UX'),
  ('UX/UI','ออกแบบเว็บไซต์และ UI/UX'),
  ('UXUI','ออกแบบเว็บไซต์และ UI/UX'),
  ('UI / UX Design','ออกแบบเว็บไซต์และ UI/UX'),
  ('Web Design','ออกแบบเว็บไซต์และ UI/UX'),
  ('capcup','ถ่ายและตัดต่อวิดีโอ'),
  ('CapCut','ถ่ายและตัดต่อวิดีโอ'),
  ('ตัดต่อวิดีโอ','ถ่ายและตัดต่อวิดีโอ'),
  ('Motion Graphic','ถ่ายและตัดต่อวิดีโอ'),
  ('เขียนคอนเทนต์','สร้างคอนเทนต์และเขียนเนื้อหา'),
  ('3D Design','ภาพประกอบและงาน 3D'),

  ('จัดเอกสาร','จัดทำและจัดเก็บเอกสาร'),
  ('พิมพ์เอกสาร','จัดทำและจัดเก็บเอกสาร'),
  ('คีย์ข้อมูล','คีย์และจัดการข้อมูล'),
  ('Microsoft Excel','จัดทำตารางและรายงาน'),
  ('รับโทรศัพท์','รับสายและประสานงาน'),
  ('จัดตารางนัดหมาย','งานธุรการและสนับสนุน'),
  ('ธุรการ','งานธุรการและสนับสนุน'),
  ('Google Workspace','งานธุรการและสนับสนุน'),
  ('microsoft','งานธุรการและสนับสนุน'),

  ('HTML / CSS','พัฒนาเว็บไซต์'),
  ('html','พัฒนาเว็บไซต์'),
  ('JavaScript','พัฒนาเว็บไซต์'),
  ('PHP','พัฒนาเว็บไซต์'),
  ('Front-end Development','พัฒนาเว็บไซต์'),
  ('Back-end Development','พัฒนาเว็บไซต์'),
  ('Full-stack Development','พัฒนาเว็บไซต์'),
  ('WordPress','พัฒนาเว็บไซต์'),
  ('vscode','พัฒนาแอปพลิเคชันและซอฟต์แวร์'),
  ('MySQL / Database','จัดการข้อมูลและฐานข้อมูล'),

  ('ตรวจนับสินค้า','งานคลังสินค้าและตรวจนับ'),
  ('แพ็กสินค้า','แพ็กและจัดเตรียมสินค้า'),
  ('ขับรถยนต์','ขับรถและขนส่ง'),
  ('ขับมอเตอร์ไซค์','ขับรถและขนส่ง'),
  ('ติดตั้งอุปกรณ์','ติดตั้งและเคลื่อนย้ายอุปกรณ์'),
  ('ใช้เครื่องมือช่าง','งานช่างและซ่อมบำรุง'),
  ('ยกของ','งานใช้แรงและงานทั่วไป');

INSERT INTO skill_consolidation_map (legacy_skill_id,broad_skill_id)
SELECT legacy.skill_id,broad.skill_id
FROM broad_skill_mapping_seed mapping
JOIN skills legacy ON legacy.skill_name=mapping.legacy_name
JOIN skills broad ON broad.skill_name=mapping.broad_name
WHERE legacy.skill_id<>broad.skill_id
ON DUPLICATE KEY UPDATE broad_skill_id=VALUES(broad_skill_id);

INSERT IGNORE INTO worker_skills (worker_user_id,skill_id,proficiency_level)
SELECT ws.worker_user_id,map.broad_skill_id,
  ELT(MAX(FIELD(ws.proficiency_level,'beginner','intermediate','advanced')),'beginner','intermediate','advanced')
FROM worker_skills ws
JOIN skill_consolidation_map map ON map.legacy_skill_id=ws.skill_id
GROUP BY ws.worker_user_id,map.broad_skill_id;

INSERT INTO job_skills (job_id,skill_id,importance)
SELECT js.job_id,map.broad_skill_id,
  IF(SUM(js.importance='required')>0,'required','preferred')
FROM job_skills js
JOIN skill_consolidation_map map ON map.legacy_skill_id=js.skill_id
GROUP BY js.job_id,map.broad_skill_id
ON DUPLICATE KEY UPDATE importance=IF(job_skills.importance='required' OR VALUES(importance)='required','required','preferred');

DELETE ws FROM worker_skills ws
JOIN skill_consolidation_map map ON map.legacy_skill_id=ws.skill_id;

DELETE js FROM job_skills js
JOIN skill_consolidation_map map ON map.legacy_skill_id=js.skill_id;

UPDATE skills
SET is_active=0,retired_at=COALESCE(retired_at,NOW())
WHERE is_custom=0
  AND skill_name NOT IN (SELECT skill_name FROM broad_skill_seed);

DELETE ws FROM worker_skills ws
JOIN skills skill ON skill.skill_id=ws.skill_id
WHERE skill.is_custom=0 AND skill.is_active=0;

DELETE js FROM job_skills js
JOIN skills skill ON skill.skill_id=js.skill_id
WHERE skill.is_custom=0 AND skill.is_active=0;

DROP TEMPORARY TABLE broad_skill_mapping_seed;
DROP TEMPORARY TABLE broad_skill_seed;
