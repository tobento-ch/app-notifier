<?php
$view->asset('assets/notifier/browser.js')->attr('type', 'module');
$view->asset('assets/js-notifier/notifier.css');
?>
<script type="module" nonce="<?= $view->esc($view->get('cspNonce', '')) ?>">
    import initBrowserNotifier from '<?= $view->assetPath('assets/notifier/browser.js') ?>';

    initBrowserNotifier({
        pullUrl: '<?= $view->esc($pullUrl) ?>',
        streamUrl: '<?= $view->esc($streamUrl) ?>',
        pollInterval: 5000,
        maxFailures: 3
    });
</script>