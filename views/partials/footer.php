<?php
$pageScript = $pageScript ?? null;
$pageScriptIsModule = (bool)($pageScriptIsModule ?? false);
?>
<script src="<?= htmlspecialchars(BASE_URL . '/assets/js/vendor/sweetalert2.all.min.js') ?>"></script>
<script src="<?= htmlspecialchars(BASE_URL . '/assets/js/sweetalert-notifications.js') ?>"></script>
<script src="<?= htmlspecialchars(BASE_URL . '/assets/js/logout.js') ?>"></script>
<?php if (!empty($pageScript)): ?>
    <?php if ($pageScriptIsModule): ?>
        <script type="module" src="<?= htmlspecialchars($pageScript) ?>"></script>
    <?php else: ?>
        <script src="<?= htmlspecialchars($pageScript) ?>"></script>
    <?php endif; ?>
<?php endif; ?>
<script src="<?= htmlspecialchars(BASE_URL . '/assets/js/spa.js') ?>"></script>
<script src="<?= htmlspecialchars(BASE_URL . '/assets/js/websocket-client.js') ?>"></script>
</body>
</html>