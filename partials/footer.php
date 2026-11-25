<?php
/**
 * partials/footer.php
 * Footer HTML umum + script Bootstrap.
 */
$cfg      = $cfg ?? require __DIR__ . '/../config/app.php';
$app_name = e($cfg['app_name'] ?? 'RS App');
?>
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- App JS -->
  <script src="/rs_app/assets/js/app.js"></script>
  <footer class="text-center text-muted small py-3 mt-auto print-hidden">
    &copy; <?= date('Y') ?> <?= $app_name ?> — Sistem Informasi Rumah Sakit
  </footer>
</body>
</html>
