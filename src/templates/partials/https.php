<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

use OCA\Passwords\AppInfo\Application;

style(Application::APP_NAME, ['app']);

$linkHttps  = 'https://wikipedia.org/wiki/HTTPS';
$linkReload = str_replace('http://', 'https://', \OC::$server->getURLGenerator()->linkToRouteAbsolute('passwords.page.index'));

$uid     = \OC::$server->getUserSession()->getUser()->getUID();
$isAdmin = \OC_User::isAdminUser($uid);

$title         = $l->t('HTTPS Required');
$reloadPage    = $l->t('reload this page');
$messageHead   = $l->t('This application requires %s in order to work safely.', ["<a href=\"{$linkHttps}\" target=\"_blank\">HTTPS</a>"]);
$messageReload = $l->t('You can try to %s the page with HTTPS.', ["<a href=\"{$linkReload}\">{$reloadPage}</a>"]);

if($isAdmin) {
    $linkDocumentation = 'https://docs.nextcloud.com/server/14/admin_manual/configuration_server/harden_server.html#use-https';
    $linkReverseProxy  = 'https://docs.nextcloud.com/server/14/admin_manual/configuration_server/reverse_proxy_configuration.html';
    $linkCertificate   = 'https://letsencrypt.org/getting-started/';
    $linkSettings      = \OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/settings/admin/passwords#passwords-https-detection');

    $readDocumentation  = $l->t('documentation');
    $messageDebugging   = $l->t('Enable %s to troubleshoot this error.', ["<a href=\"{$linkSettings}\" target=\"_blank\">HTTPS debugging</a>"]);
    $messageConfigure   = $l->t('Consult the %s to configure Nextcloud correctly.', ["<a href=\"{$linkDocumentation}\" target=\"_blank\">{$readDocumentation}</a>"]);
    $messageProxy       = $l->t('If you are using an HTTPS proxy, please read this %s.', ["<a href=\"{$linkReverseProxy}\" target=\"_blank\">{$readDocumentation}</a>"]);
    $messageCertificate = $l->t('If you do not have a HTTPS certificate, get one for free from %s.', ["<a href=\"{$linkCertificate}\" target=\"_blank\">Let's Encrypt</a>"]);
} else {
    $messageAdmin = $l->t('If this problem persists, you should contact your administrator.');
}

?>
<div id="main">
    <div class="passwords-browser-compatibility">
        <h1 class="title"><?php p($title); ?></h1>
        <div class="message">
            <b><?php print_unescaped($messageHead); ?></b>

            <br><br><?php print_unescaped($messageReload); ?><br>

            <?php if($isAdmin) : ?>
                <ul>
                    <li><?php print_unescaped($messageDebugging); ?></li>
                    <li><?php print_unescaped($messageConfigure); ?></li>
                    <li><?php print_unescaped($messageProxy); ?></li>
                    <li><?php print_unescaped($messageCertificate); ?></li>
                </ul>
            <?php else: ?>
                <br><?php print_unescaped($messageAdmin); ?>
            <?php endif; ?>
        </div>
    </div>
</div>