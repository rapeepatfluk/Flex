USE db_flexjob;
SET NAMES utf8mb4;

-- รหัสผ่านของทุกบัญชีตัวอย่างคือ Flexjob123!
INSERT INTO users (first_name, last_name, email, password_hash, role, phone) VALUES
('FLEXJOB', 'Admin', 'admin@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'admin', '0800000000'),
('Spark', 'Event Studio', 'spark@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'employer', '0811000001'),
('KIND', 'Coffee', 'kind@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'employer', '0811000002'),
('Morrow', 'Creative', 'morrow@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'employer', '0811000003'),
('มินท์', 'ศิริพร', 'mint@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'worker', '0822000001'),
('ต้น', 'ธนกฤต', 'ton@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'worker', '0822000002'),
('บัว', 'ลลิตา', 'bua@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'worker', '0822000003'),
('อาร์ต', 'พชร', 'art@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'worker', '0822000004'),
('มีน', 'กมลวรรณ', 'meen@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'worker', '0822000005'),
('นุ๊ก', 'ณัฐชา', 'nook@flexjob.test', '$2y$10$WUxalyggE0Vl3CFBadW2Q.GvO68vk7WlPxrkvaGKTipJPHUFaNyiW', 'worker', '0822000006')
ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), phone=VALUES(phone);

SET @spark_user_id = (SELECT user_id FROM users WHERE email='spark@flexjob.test');
SET @kind_user_id = (SELECT user_id FROM users WHERE email='kind@flexjob.test');
SET @morrow_user_id = (SELECT user_id FROM users WHERE email='morrow@flexjob.test');
SET @admin_user_id = (SELECT user_id FROM users WHERE email='admin@flexjob.test');

INSERT INTO employer_profiles (user_id, company_name, company_description, company_address) VALUES
(@spark_user_id, 'Spark Event Studio', 'ทีมสร้างสรรค์งานอีเวนต์และแบรนด์แอคติเวชัน', 'กรุงเทพมหานคร'),
(@kind_user_id, 'KIND Coffee', 'ร้านกาแฟสเปเชียลตี้สำหรับคนรักกาแฟ', 'กรุงเทพมหานคร'),
(@morrow_user_id, 'Morrow Creative', 'สตูดิโอครีเอทีฟและคอนเทนต์ดิจิทัล', 'กรุงเทพมหานคร')
ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), company_description=VALUES(company_description), company_address=VALUES(company_address);

INSERT INTO worker_profiles (user_id, professional_headline, biography, skills) VALUES
((SELECT user_id FROM users WHERE email='mint@flexjob.test'), 'Event Staff & Content Creator', 'ชอบงานอีเวนต์และงานคอนเทนต์ พร้อมเรียนรู้สิ่งใหม่', 'สื่อสาร, Canva, ดูแลหน้างาน'),
((SELECT user_id FROM users WHERE email='ton@flexjob.test'), 'Part-time Barista', 'สนใจงานบริการและกาแฟ', 'บริการลูกค้า, ชงกาแฟ, POS'),
((SELECT user_id FROM users WHERE email='bua@flexjob.test'), 'Freelance Graphic Designer', 'รับออกแบบงาน Social Media และสื่อดิจิทัล', 'Figma, Illustrator, Photoshop'),
((SELECT user_id FROM users WHERE email='art@flexjob.test'), 'Event Photographer', 'ช่างภาพงานอีเวนต์และภาพบุคคล', 'Photography, Lightroom, Premiere Pro'),
((SELECT user_id FROM users WHERE email='meen@flexjob.test'), 'Online Admin', 'มีประสบการณ์ดูแลร้านค้าออนไลน์และตอบแชต', 'Chat support, Excel, Canva'),
((SELECT user_id FROM users WHERE email='nook@flexjob.test'), 'Service Staff', 'พร้อมทำงานเป็นกะและประสานงานทีม', 'บริการ, สื่อสาร, ประสานงาน')
ON DUPLICATE KEY UPDATE professional_headline=VALUES(professional_headline), biography=VALUES(biography), skills=VALUES(skills);

INSERT INTO employer_documents (employer_user_id, document_file_path, document_status, reviewed_by_user_id, reviewed_at)
SELECT source.employer_user_id, source.document_file_path, 'approved', @admin_user_id, NOW()
FROM (
  SELECT @spark_user_id AS employer_user_id, 'seed/spark-criminal-record.pdf' AS document_file_path
  UNION ALL SELECT @kind_user_id, 'seed/kind-criminal-record.pdf'
  UNION ALL SELECT @morrow_user_id, 'seed/morrow-criminal-record.pdf'
) AS source
WHERE NOT EXISTS (SELECT 1 FROM employer_documents d WHERE d.employer_user_id=source.employer_user_id AND d.document_status='approved');

SET @part_time_category_id = (SELECT job_category_id FROM job_categories WHERE category_slug='part_time');
SET @event_category_id = (SELECT job_category_id FROM job_categories WHERE category_slug='event');
SET @freelance_category_id = (SELECT job_category_id FROM job_categories WHERE category_slug='freelance');

INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @spark_user_id, @event_category_id, 'Event Staff งานเปิดตัวสินค้า', 'ดูแลจุดลงทะเบียน ให้ข้อมูลผู้ร่วมงาน และช่วยประสานงานหน้างาน', 'สยามสแควร์, กรุงเทพฯ', '23–24 ส.ค. 2026', 900, 'day', 8 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='Event Staff งานเปิดตัวสินค้า');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @kind_user_id, @part_time_category_id, 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'รับออเดอร์ เตรียมเครื่องดื่ม และดูแลความเรียบร้อยในร้าน', 'อารีย์, กรุงเทพฯ', 'เลือกกะทำงานได้', 65, 'hour', 2 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='พนักงานพาร์ทไทม์ ร้านกาแฟ');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @morrow_user_id, @freelance_category_id, 'Graphic Designer (Freelance)', 'ออกแบบสื่อ Social Media สำหรับแคมเปญ จำนวน 10 ชิ้นต่อโปรเจกต์', 'ทำงานออนไลน์', 'ปิดรับ 30 ส.ค. 2026', 3500, 'project', 1 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='Graphic Designer (Freelance)');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @spark_user_id, @event_category_id, 'ทีมลงทะเบียนงานวิ่งการกุศล', 'ต้อนรับผู้ร่วมงาน แจกเบอร์วิ่ง และช่วยดูแลจุดลงทะเบียน', 'สวนลุมพินี, กรุงเทพฯ', '31 ส.ค. 2026 เวลา 04:30–10:00', 850, 'day', 12 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='ทีมลงทะเบียนงานวิ่งการกุศล');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @kind_user_id, @part_time_category_id, 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'รับออเดอร์ ชงเครื่องดื่ม และดูแลพื้นที่หน้าร้าน มีการสอนงาน', 'ทองหล่อ, กรุงเทพฯ', 'เสาร์-อาทิตย์ 08:00–17:00', 70, 'hour', 2 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='Barista พาร์ทไทม์ วันเสาร์-อาทิตย์');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @morrow_user_id, @freelance_category_id, 'ช่างภาพงานอีเวนต์', 'ถ่ายภาพบรรยากาศและกิจกรรมภายในงาน พร้อมคัดเลือกภาพส่งหลังจบงาน', 'ไอคอนสยาม, กรุงเทพฯ', '6 ก.ย. 2026', 4500, 'project', 1 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='ช่างภาพงานอีเวนต์');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @kind_user_id, @part_time_category_id, 'แอดมินตอบแชต (Work from Home)', 'ตอบคำถามลูกค้าและประสานงานทีมขาย มีคู่มือข้อความให้', 'ทำงานออนไลน์', 'จ.–ศ. 10:00–18:00', 700, 'day', 3 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='แอดมินตอบแชต (Work from Home)');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @spark_user_id, @event_category_id, 'Staff แจกสินค้าตัวอย่าง', 'แจกสินค้าตัวอย่างและเชิญชวนผู้ร่วมงานเข้าร่วมกิจกรรมแบรนด์', 'เซ็นทรัลเวิลด์, กรุงเทพฯ', '12–14 ก.ย. 2026', 950, 'day', 6 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='Staff แจกสินค้าตัวอย่าง');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @morrow_user_id, @freelance_category_id, 'Content Creator สำหรับ TikTok', 'คิดคอนเทนต์ ถ่าย และตัดต่อวิดีโอ TikTok จำนวน 5 คลิป', 'ทำงานออนไลน์', 'ส่งงานภายใน 14 วัน', 6000, 'project', 1 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='Content Creator สำหรับ TikTok');
INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_schedule, pay_amount, pay_unit, open_positions)
SELECT @kind_user_id, @part_time_category_id, 'พนักงานเสิร์ฟงานเลี้ยง', 'เสิร์ฟอาหารและเครื่องดื่ม ช่วยจัดโต๊ะ และดูแลความเรียบร้อยในงาน', 'เชียงใหม่', '20 ก.ย. 2026 เวลา 16:00–23:00', 800, 'day', 5 WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE job_title='พนักงานเสิร์ฟงานเลี้ยง');
