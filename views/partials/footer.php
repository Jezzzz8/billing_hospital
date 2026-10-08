<?php
$pageScript = $pageScript ?? null;
$pageScriptIsModule = (bool)($pageScriptIsModule ?? false);
?>
<script src="<?= htmlspecialchars(BASE_URL . '/assets/js/logout.js') ?>"></script>
<?php if (!empty($pageScript)): ?>
    <?php if ($pageScriptIsModule): ?>
        <script type="module" src="<?= htmlspecialchars($pageScript) ?>"></script>
    <?php else: ?>
        <script src="<?= htmlspecialchars($pageScript) ?>"></script>
    <?php endif; ?>
<?php endif; ?>
<script>
    window.refreshAdminList = window.refreshAdminList || (() => window.location.reload());
    document.addEventListener('DOMContentLoaded', () => {
        document.dispatchEvent(new CustomEvent('spa:page-loaded', {
            detail: { page: document.body.dataset.page || '' }
        }));
    });
</script>
</body>
</html>