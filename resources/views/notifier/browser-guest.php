<?php
$view->asset('assets/notifier/browser-guest.js')->attr('type', 'module');
$view->asset('assets/js-notifier/notifier.css');
?>
<script type="module" nonce="<?= $view->esc($view->get('cspNonce', '')) ?>">
    import initBrowserNotifier from '<?= $view->assetPath('assets/notifier/browser-guest.js') ?>';

    initBrowserNotifier({
        pullUrl: '<?= $view->esc($pullUrl) ?>',
        pollInterval: 5000,
        maxFailures: 3
    });
</script>