UPDATE employer_profiles ep
JOIN users u ON u.user_id = ep.user_id
SET ep.company_address = CASE u.email
    WHEN 'employer@gmail.com' THEN '444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'kind@flexjob.test' THEN '99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'morrow@flexjob.test' THEN '156 ถนนรมย์บุรี ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    ELSE ep.company_address
END
WHERE u.email IN ('employer@gmail.com', 'kind@flexjob.test', 'morrow@flexjob.test');

UPDATE jobs
SET work_location = CASE job_title
    WHEN 'Event Staff งานเปิดตัวสินค้า' THEN 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'พนักงานพาร์ทไทม์ ร้านกาแฟ' THEN 'KIND Coffee, 99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'Graphic Designer (Freelance)' THEN 'ทำงานออนไลน์ (สำนักงาน Morrow Creative, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)'
    WHEN 'ทีมลงทะเบียนงานวิ่งการกุศล' THEN 'สวนรมย์บุรี ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์' THEN 'KIND Coffee, 99/9 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'ช่างภาพงานอีเวนต์' THEN 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'แอดมินตอบแชต (Work from Home)' THEN 'ทำงานออนไลน์ (สำนักงานใหญ่ KIND Coffee, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)'
    WHEN 'Staff แจกสินค้าตัวอย่าง' THEN 'ทวีกิจซูเปอร์เซ็นเตอร์ ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'Content Creator สำหรับ TikTok' THEN 'ทำงานออนไลน์ (สำนักงาน Morrow Creative, ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)'
    WHEN 'พนักงานเสิร์ฟงานเลี้ยง' THEN 'โรงแรมเทพนคร ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'ต้องการนักเขียนโปรแกรม' THEN 'ABC Company, ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'นักเขียนโปรแกรม' THEN '134 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    WHEN 'ออกแบบ UX / UI' THEN 'ทำงานออนไลน์ (สำนักงาน 134 ถนนจิระ ตำบลในเมือง อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000)'
    WHEN 'คนยืนบูธงาน MotoGP' THEN 'สนามช้าง อินเตอร์เนชั่นแนล เซอร์กิต, 444 หมู่ 15 ตำบลอิสาณ อำเภอเมืองบุรีรัมย์ จังหวัดบุรีรัมย์ 31000'
    ELSE work_location
END,
work_province = 'บุรีรัมย์',
work_mode = CASE job_title
    WHEN 'Graphic Designer (Freelance)' THEN 'remote'
    WHEN 'แอดมินตอบแชต (Work from Home)' THEN 'remote'
    WHEN 'Content Creator สำหรับ TikTok' THEN 'remote'
    WHEN 'ออกแบบ UX / UI' THEN 'remote'
    ELSE work_mode
END
WHERE job_title IN ('Event Staff งานเปิดตัวสินค้า', 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'Graphic Designer (Freelance)', 'ทีมลงทะเบียนงานวิ่งการกุศล', 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'ช่างภาพงานอีเวนต์', 'แอดมินตอบแชต (Work from Home)', 'Staff แจกสินค้าตัวอย่าง', 'Content Creator สำหรับ TikTok', 'พนักงานเสิร์ฟงานเลี้ยง', 'ต้องการนักเขียนโปรแกรม', 'นักเขียนโปรแกรม', 'ออกแบบ UX / UI', 'คนยืนบูธงาน MotoGP');

DELETE js FROM job_skills js
JOIN jobs j ON j.job_id = js.job_id
WHERE j.job_title IN ('Event Staff งานเปิดตัวสินค้า', 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'Graphic Designer (Freelance)', 'ทีมลงทะเบียนงานวิ่งการกุศล', 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'ช่างภาพงานอีเวนต์', 'แอดมินตอบแชต (Work from Home)', 'Staff แจกสินค้าตัวอย่าง', 'Content Creator สำหรับ TikTok', 'พนักงานเสิร์ฟงานเลี้ยง', 'ต้องการนักเขียนโปรแกรม', 'นักเขียนโปรแกรม', 'ออกแบบ UX / UI', 'คนยืนบูธงาน MotoGP');

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
    SELECT 'พนักงานเสิร์ฟงานเลี้ยง', 'บริการและดูแลลูกค้า' UNION ALL
    SELECT 'ต้องการนักเขียนโปรแกรม', 'พัฒนาเว็บไซต์' UNION ALL
    SELECT 'ต้องการนักเขียนโปรแกรม', 'พัฒนาแอปพลิเคชันและซอฟต์แวร์' UNION ALL
    SELECT 'นักเขียนโปรแกรม', 'พัฒนาเว็บไซต์' UNION ALL
    SELECT 'นักเขียนโปรแกรม', 'พัฒนาแอปพลิเคชันและซอฟต์แวร์' UNION ALL
    SELECT 'ออกแบบ UX / UI', 'ออกแบบเว็บไซต์และ UI/UX' UNION ALL
    SELECT 'คนยืนบูธงาน MotoGP', 'ดูแลบูธและผู้ร่วมงาน' UNION ALL
    SELECT 'คนยืนบูธงาน MotoGP', 'ต้อนรับและให้ข้อมูล'
) mapping ON mapping.job_title = j.job_title
JOIN skills s ON s.skill_name = mapping.skill_name AND s.is_active = 1 AND s.is_custom = 0;

DELETE jwm FROM job_worker_matches jwm
JOIN jobs j ON j.job_id = jwm.job_id
WHERE j.job_title IN ('Event Staff งานเปิดตัวสินค้า', 'พนักงานพาร์ทไทม์ ร้านกาแฟ', 'Graphic Designer (Freelance)', 'ทีมลงทะเบียนงานวิ่งการกุศล', 'Barista พาร์ทไทม์ วันเสาร์-อาทิตย์', 'ช่างภาพงานอีเวนต์', 'แอดมินตอบแชต (Work from Home)', 'Staff แจกสินค้าตัวอย่าง', 'Content Creator สำหรับ TikTok', 'พนักงานเสิร์ฟงานเลี้ยง', 'ต้องการนักเขียนโปรแกรม', 'นักเขียนโปรแกรม', 'ออกแบบ UX / UI', 'คนยืนบูธงาน MotoGP');
