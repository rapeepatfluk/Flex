USE db_flexjob;
SET NAMES utf8mb4;

-- ใช้กับบัญชีตัวอย่างที่มีอยู่แล้ว: employer@gmail.com, kind@flexjob.test และ morrow@flexjob.test
DELETE FROM job_images;
DELETE FROM jobs;

UPDATE employer_profiles ep
JOIN users u ON u.user_id = ep.user_id
SET ep.company_name = CASE u.email
    WHEN 'employer@gmail.com' THEN 'Spark Event Studio'
    WHEN 'kind@flexjob.test' THEN 'KIND Coffee'
    WHEN 'morrow@flexjob.test' THEN 'Morrow Creative'
END,
ep.company_description = CASE u.email
    WHEN 'employer@gmail.com' THEN 'ทีมสร้างสรรค์งานอีเวนต์และแบรนด์แอคติเวชัน'
    WHEN 'kind@flexjob.test' THEN 'ร้านกาแฟสเปเชียลตี้สำหรับคนรักกาแฟ'
    WHEN 'morrow@flexjob.test' THEN 'สตูดิโอครีเอทีฟและคอนเทนต์ดิจิทัล'
END,
ep.company_address = CASE u.email
    WHEN 'employer@gmail.com' THEN '444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'kind@flexjob.test' THEN '99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'morrow@flexjob.test' THEN '156 ถนนรมย์บุรี ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
END,
ep.company_logo_path = CASE u.email
    WHEN 'employer@gmail.com' THEN 'assets/images/spark-event-logo.svg'
    WHEN 'kind@flexjob.test' THEN 'assets/images/kind-coffee-logo.svg'
    WHEN 'morrow@flexjob.test' THEN 'assets/images/morrow-creative-logo.svg'
END
WHERE u.email IN ('employer@gmail.com', 'kind@flexjob.test', 'morrow@flexjob.test');

SET @spark_user_id = (SELECT user_id FROM users WHERE email = 'employer@gmail.com');
SET @kind_user_id = (SELECT user_id FROM users WHERE email = 'kind@flexjob.test');
SET @morrow_user_id = (SELECT user_id FROM users WHERE email = 'morrow@flexjob.test');
SET @part_time_category_id = (SELECT job_category_id FROM job_categories WHERE category_slug = 'part_time');
SET @event_category_id = (SELECT job_category_id FROM job_categories WHERE category_slug = 'event');
SET @freelance_category_id = (SELECT job_category_id FROM job_categories WHERE category_slug = 'freelance');

INSERT INTO jobs (employer_user_id, job_category_id, job_title, job_description, work_location, work_province, work_schedule, work_mode, pay_amount, pay_unit, open_positions, job_status) VALUES
(@spark_user_id, @event_category_id, 'Event Staff งานเปิดตัวสินค้า', 'ดูแลจุดลงทะเบียน ให้ข้อมูลผู้ร่วมงาน และช่วยประสานงานหน้างาน', 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '23–24 ส.ค. 2026 เวลา 09:00–18:00', 'onsite', 900, 'day', 8, 'published'),
(@kind_user_id, @part_time_category_id, 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'รับออเดอร์ เตรียมเครื่องดื่ม และดูแลความเรียบร้อยภายในร้าน', 'KIND Coffee, 99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', 'เลือกกะทำงานได้', 'onsite', 70, 'hour', 2, 'published'),
(@morrow_user_id, @freelance_category_id, 'Graphic Designer (Freelance)', 'ออกแบบสื่อ Social Media สำหรับแคมเปญ จำนวน 10 ชิ้นต่อโปรเจกต์', 'ทำงานออนไลน์ (สำนักงาน Morrow Creative, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', 'ปิดรับ 30 ส.ค. 2026', 'remote', 3500, 'project', 1, 'published'),
(@spark_user_id, @event_category_id, 'ทีมลงทะเบียนงานวิ่งการกุศล', 'ต้อนรับผู้ร่วมงาน แจกเบอร์วิ่ง และช่วยดูแลจุดลงทะเบียน', 'สวนรมย์บุรี ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '31 ส.ค. 2026 เวลา 04:30–10:00', 'onsite', 850, 'day', 12, 'published'),
(@kind_user_id, @part_time_category_id, 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'ชงกาแฟ รับออเดอร์ และดูแลความเรียบร้อยหน้าร้าน มีการสอนงาน', 'KIND Coffee, 99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', 'เสาร์–อาทิตย์ 08:00–17:00', 'onsite', 75, 'hour', 2, 'published'),
(@morrow_user_id, @freelance_category_id, 'ช่างภาพงานอีเวนต์', 'ถ่ายภาพบรรยากาศและกิจกรรมภายในงาน พร้อมคัดเลือกภาพส่งหลังจบงาน', 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '6 ก.ย. 2026', 'onsite', 4500, 'project', 1, 'published'),
(@kind_user_id, @part_time_category_id, 'แอดมินตอบแชต (Work from Home)', 'ตอบคำถามลูกค้าและประสานงานทีมขาย มีคู่มือข้อความให้', 'ทำงานออนไลน์ (สำนักงานใหญ่ KIND Coffee, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', 'จ.–ศ. 10:00–18:00', 'remote', 700, 'day', 3, 'published'),
(@spark_user_id, @event_category_id, 'Staff แจกสินค้าตัวอย่าง', 'แจกสินค้าตัวอย่างและเชิญชวนผู้ร่วมงานเข้าร่วมกิจกรรมแบรนด์', 'ทวีกิจซูเปอร์เซ็นเตอร์ ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '12–14 ก.ย. 2026', 'onsite', 950, 'day', 6, 'published'),
(@morrow_user_id, @freelance_category_id, 'Content Creator สำหรับ TikTok', 'คิดคอนเทนต์ ถ่าย และตัดต่อวิดีโอ TikTok จำนวน 5 คลิป', 'ทำงานออนไลน์ (สำนักงาน Morrow Creative, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)', 'บุรีรัมย์', 'ส่งงานภายใน 14 วัน', 'remote', 6000, 'project', 1, 'published'),
(@kind_user_id, @part_time_category_id, 'พนักงานเสิร์ฟงานเลี้ยง', 'เสิร์ฟอาหารและเครื่องดื่ม ช่วยจัดโต๊ะ และดูแลความเรียบร้อยในงาน', 'โรงแรมเทพนคร ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000', 'บุรีรัมย์', '20 ก.ย. 2026 เวลา 16:00–23:00', 'onsite', 800, 'day', 5, 'published');

INSERT INTO job_images (job_id, image_file_path, display_order)
SELECT job_id, 'assets/images/job-event-staff-v1.png', 1 FROM jobs WHERE job_title IN ('Event Staff งานเปิดตัวสินค้า', 'ทีมลงทะเบียนงานวิ่งการกุศล', 'Staff แจกสินค้าตัวอย่าง')
UNION ALL SELECT job_id, 'assets/images/job-barista-v1.png', 1 FROM jobs WHERE job_title IN ('พนักงานพาร์ทไทม์ ร้านกาแฟ', 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'แอดมินตอบแชต (Work from Home)', 'พนักงานเสิร์ฟงานเลี้ยง')
UNION ALL SELECT job_id, 'assets/images/job-creative-v1.png', 1 FROM jobs WHERE job_title IN ('Graphic Designer (Freelance)', 'ช่างภาพงานอีเวนต์', 'Content Creator สำหรับ TikTok');

INSERT INTO job_skills (job_id, skill_id, importance)
SELECT j.job_id, s.skill_id, 'required'
FROM jobs j
JOIN (
    SELECT 'Event Staff งานเปิดตัวสินค้า' AS job_title, 'ต้อนรับและลงทะเบียน' AS skill_name UNION ALL
    SELECT 'Event Staff งานเปิดตัวสินค้า', 'ประสานงานอีเวนต์' UNION ALL
    SELECT 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'งานอาหารและเครื่องดื่ม' UNION ALL
    SELECT 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'รับออเดอร์และชำระเงิน' UNION ALL
    SELECT 'Graphic Designer (Freelance)', 'ออกแบบกราฟิก' UNION ALL
    SELECT 'ทีมลงทะเบียนงานวิ่งการกุศล', 'ต้อนรับและลงทะเบียน' UNION ALL
    SELECT 'ทีมลงทะเบียนงานวิ่งการกุศล', 'ดูแลคิวและกิจกรรม' UNION ALL
    SELECT 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'งานอาหารและเครื่องดื่ม' UNION ALL
    SELECT 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'รับออเดอร์และชำระเงิน' UNION ALL
    SELECT 'ช่างภาพงานอีเวนต์', 'ถ่ายภาพ' UNION ALL
    SELECT 'แอดมินตอบแชต (Work from Home)', 'บริการและดูแลลูกค้า' UNION ALL
    SELECT 'แอดมินตอบแชต (Work from Home)', 'รับสายและประสานงาน' UNION ALL
    SELECT 'Staff แจกสินค้าตัวอย่าง', 'ขายและแนะนำสินค้า' UNION ALL
    SELECT 'Staff แจกสินค้าตัวอย่าง', 'ดูแลบูธและผู้ร่วมงาน' UNION ALL
    SELECT 'Content Creator สำหรับ TikTok', 'สร้างคอนเทนต์และเขียนเนื้อหา' UNION ALL
    SELECT 'Content Creator สำหรับ TikTok', 'ถ่ายและตัดต่อวิดีโอ' UNION ALL
    SELECT 'พนักงานเสิร์ฟงานเลี้ยง', 'งานอาหารและเครื่องดื่ม' UNION ALL
    SELECT 'พนักงานเสิร์ฟงานเลี้ยง', 'บริการและดูแลลูกค้า'
) mapping ON mapping.job_title = j.job_title
JOIN skills s ON s.skill_name = mapping.skill_name AND s.is_active = 1 AND s.is_custom = 0;
