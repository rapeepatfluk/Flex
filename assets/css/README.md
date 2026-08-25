# FLEXJOB CSS guide

โปรเจกต์ใช้ **Bootstrap 5.3** เป็นพื้นฐานสำหรับโครงสร้างหน้าเว็บ เช่น `container`, `row`, `col-*`, `card`, `btn`, `form-control`, `form-select`, `alert`, `badge` และ utility สำหรับระยะห่าง/การจัดวาง (`d-flex`, `gap-*`, `py-*`, `mb-*`) 

ไฟล์ CSS ด้านล่างจึงเก็บเฉพาะสิ่งที่ Bootstrap ทำแทนไม่ได้ หรือเป็นภาพลักษณ์เฉพาะของ FLEXJOB

| ไฟล์ | หน้าที่ | ส่วนที่ควรแก้ในไฟล์นี้ |
| --- | --- | --- |
| `app.css` | สไตล์ร่วมของเว็บรุ่นเดิม: โลโก้ Header พื้นฐาน, หน้าแรก, รายการงาน, Dashboard และสถานะต่าง ๆ | ปรับโครงสร้างหน้าสาธารณะเก่าหรือ Dashboard ที่ยังไม่ได้ย้ายเป็น Bootstrap เต็มรูปแบบ |
| `theme.css` | สีหลักของ FLEXJOB และ Bootstrap variables เช่น สีพื้นหลัง, สีตัวอักษร, สีปุ่ม และเส้นขอบ | เปลี่ยน palette ของทั้งเว็บ หรือพฤติกรรมปุ่ม Bootstrap |
| `header.css` | เมนูบัญชี, avatar, กระดิ่งแจ้งเตือน, dropdown และ responsive ของ Header | ปรับเฉพาะการทำงาน/หน้าตาของเมนูด้านบน |
| `header-theme.css` | รายละเอียดสีและ hover ของ Header สีฟ้า รวมถึง account dropdown | ปรับสี Header โดยไม่กระทบเนื้อหาหน้าอื่น |
| `index.css` | หน้าแรกก่อน Login: Hero, ช่องค้นหา, หมวดงาน, งานแนะนำ, ตัวกรอง, CTA และพื้นที่โฆษณา | ปรับการตกแต่งเฉพาะหน้า `index.php` |
| `index-how.css` | ส่วน “How FLEXJOB Works” ของหน้าแรก | ปรับภาพโทรศัพท์/ลำดับขั้นตอนหน้าแรก |
| `worker-index.css` | การ์ดงานบนหน้าแรกของ Worker | ปรับภาพงาน, hover และข้อมูลใน card |
| `worker-profile-guide.css` | แบนเนอร์แนะนำการอัปโหลด Resume และ Portfolio | ปรับขนาดภาพและข้อความแนะนำโปรไฟล์ |
| `worker-how.css` | ส่วนขั้นตอนใช้งานของ Worker | ปรับ mockup โทรศัพท์และเนื้อหาขั้นตอน |
| `employer-index.css` | หน้าแรกของ Employer: แบนเนอร์แนะนำ, ขั้นตอนเริ่มจ้าง และการ์ดงาน | ปรับเฉพาะประสบการณ์ผู้ว่าจ้าง |
| `job-detail.css` | ความสูงและการแสดงผลภาพประกอบในหน้ารายละเอียดงาน | ปรับขนาด cover image ของ `job.php` |

## แนวทางแก้ CSS ต่อจากนี้

1. ใช้ Bootstrap ก่อนเสมอสำหรับ layout, spacing, form, button, card, alert และ responsive grid
2. เพิ่ม CSS ใหม่เมื่อเป็นงานเฉพาะของแบรนด์ เช่น Hero, ภาพประกอบ, animation, cover image หรือ dropdown แบบพิเศษ
3. เก็บสีไว้ใน `theme.css` ผ่าน CSS variables แทนการใส่รหัสสีซ้ำในหลายไฟล์
4. ใช้ชื่อ class ให้สื่อหน้าที่ เช่น `worker-job-card`, `profile-guide-banner` เพื่อไม่ชนกับ Bootstrap
