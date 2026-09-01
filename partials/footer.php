<footer class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 px-4 py-4"><a class="brand" href="<?= BASE_URL ?>/index.php"><span class="brand-mark">F</span>FLEX<span>JOB</span></a>
    <p>งานที่ใช่ ในเวลาที่ยืดหยุ่น</p><span>© 2026 FLEXJOB</span>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (user()): ?><script src="<?= BASE_URL ?>/assets/js/notifications.js?v=<?= filemtime(APP_ROOT . '/assets/js/notifications.js') ?>"></script><?php endif; ?>
<script>
(function () {
    var link = document.getElementById('howItWorksLink');
    if (!link) return;
    link.addEventListener('click', function (e) {
        var section = document.getElementById('how');
        if (section) {
            e.preventDefault();
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            history.replaceState(null, '', '#how');
        }
        // else: ไปหน้า index.php#how ตามปกติ
    });
    // กรณีเข้าหน้าด้วย #how ใน URL (เช่น navigate จากหน้าอื่น)
    if (window.location.hash === '#how') {
        var section = document.getElementById('how');
        if (section) {
            setTimeout(function () {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
})();
</script>
</body>

</html>
