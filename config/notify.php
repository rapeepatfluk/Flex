<?php
/**
 * Email notification helpers for FLEXJOB
 * Call these after DB changes — they send email silently (fail-safe)
 */

require_once __DIR__ . '/mailer.php';

/**
 * Notify a worker when their application status changes
 */
function notify_worker_status(int $appId): void
{
    try {
        $s = db()->prepare("
            SELECT
                a.application_status AS status,
                a.worker_user_id,
                j.job_title AS title,
                ep.company_name,
                CONCAT(u.first_name,' ',u.last_name) AS worker_name,
                u.email AS worker_email
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            JOIN employer_profiles ep ON ep.user_id = j.employer_user_id
            JOIN users u ON u.user_id = a.worker_user_id
            WHERE a.application_id = ?
        ");
        $s->execute([$appId]);
        $row = $s->fetch();
        if (!$row) return;

        $statusLabel = [
            'eligible'          => 'มีสิทธิ์สัมภาษณ์ 🎉',
            'interview_passed'  => 'ผ่านสัมภาษณ์แล้ว 🎉',
            'completed'         => 'งานเสร็จสิ้น — ให้คะแนนได้',
            'not_selected' => 'ไม่ผ่านการคัดเลือก',
            'submitted'    => 'รอพิจารณา',
        ][$row['status']] ?? $row['status'];

        $statusColor = match($row['status']) {
            'eligible'          => '#19663f',
            'interview_passed'  => '#047857',
            'completed'         => '#0052cc',
            'not_selected' => '#bd4d3d',
            default        => '#8a6100',
        };

        $dashboardUrl = BASE_URL . '/worker/application-detail.php?id=' . $appId;
        $appUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dashboardUrl;

        notification_create(
            db(),
            (int) $row['worker_user_id'],
            'อัปเดตสถานะใบสมัคร',
            'งาน “' . $row['title'] . '” เปลี่ยนสถานะเป็น ' . $statusLabel,
            'worker/application-detail.php?id=' . $appId
        );

        $btnHtml = $row['status'] !== 'submitted'
            ? email_btn($appUrl, 'ดูรายละเอียดการสมัคร')
            : '';

        $body = <<<HTML
<h2 style="margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;">สวัสดี, {$row['worker_name']}</h2>
<p style="margin:0 0 24px;color:#697671;font-size:15px;">มีการอัปเดตสถานะใบสมัครของคุณ</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8f4;border-radius:12px;overflow:hidden;margin-bottom:24px;">
  <tr>
    <td style="padding:20px 24px;">
      <p style="margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;">ตำแหน่งงาน</p>
      <p style="margin:0;font-size:18px;font-weight:700;color:#17231f;">{$row['title']}</p>
      <p style="margin:4px 0 0;font-size:14px;color:#697671;">{$row['company_name']}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:0 24px 20px;">
      <p style="margin:0 0 6px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;">สถานะปัจจุบัน</p>
      <span style="display:inline-block;padding:6px 14px;background:#edf0ef;border-radius:20px;font-size:14px;font-weight:600;color:{$statusColor};">{$statusLabel}</span>
    </td>
  </tr>
</table>

{$btnHtml}

<p style="font-size:13px;color:#697671;margin-top:8px;">หากมีข้อสงสัย สามารถติดต่อผู้ว่าจ้างได้โดยตรงผ่านข้อมูลติดต่อในหน้ารายละเอียดการสมัคร</p>
HTML;

        send_mail(
            $row['worker_email'],
            $row['worker_name'],
            "อัปเดตสถานะใบสมัคร: {$row['title']} — {$row['company_name']}",
            $body
        );
    } catch (\Throwable) {
        // Fail silently — don't break the main flow
    }
}

/**
 * Notify employer when a new applicant applies for their job
 */
function notify_employer_new_applicant(int $appId): void
{
    try {
        $s = db()->prepare("
            SELECT
                j.job_title AS title,
                CONCAT(uw.first_name,' ',uw.last_name) AS worker_name,
                uw.email AS worker_email,
                uw.phone AS worker_phone,
                a.cover_note,
                a.job_id,
                CONCAT(ue.first_name,' ',ue.last_name) AS employer_name,
                ue.email AS employer_email
            FROM applications a
            JOIN jobs j ON j.job_id = a.job_id
            JOIN users uw ON uw.user_id = a.worker_user_id
            JOIN users ue ON ue.user_id = j.employer_user_id
            WHERE a.application_id = ?
        ");
        $s->execute([$appId]);
        $row = $s->fetch();
        if (!$row) return;

        $applicantsUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . BASE_URL . '/employer/applicants.php?job=' . $row['job_id'];

        $coverNote = $row['cover_note']
            ? '<blockquote style="margin:0 0 20px;padding:12px 16px;border-left:4px solid #d7f56d;background:#f7f8f4;border-radius:0 8px 8px 0;font-size:13px;color:#506059;">'
              . htmlspecialchars($row['cover_note'], ENT_QUOTES, 'UTF-8')
              . '</blockquote>'
            : '';

        $body = <<<HTML
<h2 style="margin:0 0 8px;font-size:24px;color:#17231f;letter-spacing:-1px;">มีผู้สมัครงานใหม่! 🎯</h2>
<p style="margin:0 0 24px;color:#697671;font-size:15px;">สวัสดี {$row['employer_name']} — มีคนสมัครงานของคุณ</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f8f4;border-radius:12px;margin-bottom:20px;">
  <tr><td style="padding:20px 24px;">
    <p style="margin:0 0 4px;font-size:12px;color:#697671;text-transform:uppercase;letter-spacing:1px;">ตำแหน่งงาน</p>
    <p style="margin:0;font-size:18px;font-weight:700;color:#17231f;">{$row['title']}</p>
  </td></tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e0e8e3;border-radius:12px;overflow:hidden;margin-bottom:20px;">
  <tr style="background:#166b54;">
    <td colspan="2" style="padding:14px 20px;">
      <div style="display:inline-block;width:36px;height:36px;border-radius:50%;background:#d7f56d;text-align:center;line-height:36px;font-weight:700;font-size:16px;color:#166b54;float:left;margin-right:12px;">
        {$row['worker_name'][0]}
      </div>
      <span style="color:#ffffff;font-size:16px;font-weight:600;line-height:36px;">{$row['worker_name']}</span>
    </td>
  </tr>
  <tr>
    <td style="padding:12px 20px;font-size:13px;color:#697671;border-bottom:1px solid #e0e8e3;">📧 อีเมล</td>
    <td style="padding:12px 20px;font-size:13px;font-weight:600;border-bottom:1px solid #e0e8e3;">{$row['worker_email']}</td>
  </tr>
  <tr>
    <td style="padding:12px 20px;font-size:13px;color:#697671;">📞 โทรศัพท์</td>
    <td style="padding:12px 20px;font-size:13px;font-weight:600;">{$row['worker_phone']}</td>
  </tr>
</table>

{$coverNote}
HTML;
        $body .= email_btn($applicantsUrl, 'ดูผู้สมัครทั้งหมด →');

        send_mail(
            $row['employer_email'],
            $row['employer_name'],
            "มีผู้สมัครงานใหม่: {$row['title']}",
            $body
        );
    } catch (\Throwable) {
        // Fail silently
    }
}
