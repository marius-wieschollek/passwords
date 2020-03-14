<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

use OCA\Passwords\AppInfo\Application;

style(Application::APP_NAME, ['app']);
script(Application::APP_NAME, ['Static/https-debug']);

$linkHttps  = 'https://wikipedia.org/wiki/HTTPS';
$linkReload = str_replace('http://', 'https://', \OC::$server->getURLGenerator()->linkToRouteAbsolute('passwords.page.index'));

$uid     = \OC::$server->getUserSession()->getUser()->getUID();
$isAdmin = \OC_User::isAdminUser($uid);

$title         = $l->t('HTTPS Required');
$reloadPage    = $l->t('reload this page');
$messageHead   = $l->t('This application requires %s in order to work safely.', ["<a href='{$linkHttps}' target='_blank'>HTTPS</a>"]);
$messageReload = $l->t('You can try to %s the page with HTTPS.', ["<a href='{$linkReload}'>{$reloadPage}</a>"]);

if($isAdmin) {
    $linkDocumentation = 'https://docs.nextcloud.com/server/latest/admin_manual/installation/harden_server.html#use-https-label';
    $linkReverseProxy  = 'https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/reverse_proxy_configuration.html';
    $linkForum         = 'https://help.nextcloud.com/c/apps/passwords';
    $linkCertificate   = 'https://letsencrypt.org/getting-started/';

    $l10nForum          = $l->t('forum');
    $l10nDocs           = $l->t('documentation');
    $messageNoBug       = $l->t('This is NOT a bug. Visit our %s if you need help.', ["<a href='{$linkForum}' target='_blank'>{$l10nForum}</a>"]);
    $messageDebugging   = $l->t('Review the HTTPS report below to debug the issue.');
    $messageConfigure   = $l->t('Read this %s to configure your server to use HTTPS.', ["<a href='{$linkDocumentation}' target='_blank'>{$l10nDocs}</a>"]);
    $messageProxy       = $l->t('Read this %s if you are using any kind of proxy.', ["<a href='{$linkReverseProxy}' target='_blank'>{$l10nDocs}</a>"]);
    $messageCertificate = $l->t('Go to %s to ge a free HTTPS certificate if you need one.', ["<a href='{$linkCertificate}' target='_blank'>Let's Encrypt</a>"]);
} else {
    $messageAdmin = $l->t('If this problem persists, you should contact your administrator.');
}

?>
<div id="main">
    <div class="passwords-https-report">
        <h1 class="title"><?php p($title); ?></h1>
        <div class="message">
            <b><?php print_unescaped($messageHead); ?></b>

            <br><br><?php print_unescaped($messageReload); ?><br>

            <?php if($isAdmin) : ?>
                <ol>
                    <li><b><?php print_unescaped($messageNoBug); ?></b></li>
                    <li><?php print_unescaped($messageDebugging); ?></li>
                    <li><?php print_unescaped($messageConfigure); ?></li>
                    <li><?php print_unescaped($messageProxy); ?></li>
                    <li><?php print_unescaped($messageCertificate); ?></li>
                </ol>
            <?php else: ?>
                <br><?php print_unescaped($messageAdmin); ?>
            <?php endif; ?>
        </div>
        <?php
        if($isAdmin) print_unescaped($this->inc('partials/https_debug'));
        ?>
    </div>
</div>