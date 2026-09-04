<footer class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 px-4 py-4"><a class="brand" href="<?= BASE_URL ?>/index.php"><span class="brand-mark">F</span>FLEX<span>JOB</span></a>
    <p>งานที่ใช่ ในเวลาที่ยืดหยุ่น</p><span>© 2026 FLEXJOB</span>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (user()): ?><script src="<?= BASE_URL ?>/assets/js/notifications.js?v=<?= filemtime(APP_ROOT . '/assets/js/notifications.js') ?>"></script><?php endif; ?>
<?php foreach ($pageScripts ?? [] as $script): $scriptFile = APP_ROOT . '/assets/js/' . $script . '.js'; if (is_file($scriptFile)): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= e($script) ?>.js?v=<?= filemtime($scriptFile) ?>"></script>
<?php endif; endforeach; ?>
</body>

</html>
