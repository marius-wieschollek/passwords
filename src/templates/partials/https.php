<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

use OCA\Passwords\AppInfo\Application;

style(Application::APP_NAME, ['app']);
script(Application::APP_NAME, ['Helper/compatibility']);

$linkHttps         = 'https://wikipedia.org/wiki/HTTPS';
$linkDocumentation = 'https://docs.nextcloud.com/server/12/admin_manual/configuration_server/harden_server.html#use-https';
$linkCertificate   = 'https://letsencrypt.org/getting-started/';

$title              = $l->t('HTTPS Required');
$reloadPage         = $l->t('reload this page');
$readDocumentation  = $l->t('documentation');
$messageHead        = $l->t('This application requires %s in order to work safely.', ["<a href=\"{$linkHttps}\" target=\"_blank\">HTTPS</a>"]);
$messageConfigure   = $l->t('Consult the %s to configure Nextcloud correctly.', ["<a href=\"{$linkDocumentation}\" target=\"_blank\">{$readDocumentation}</a>"]);
$messageCertificate = $l->t('If you do not have a HTTPS certificate, get one for free from %s.', ["<a href=\"{$linkCertificate}\" target=\"_blank\">Let's Encrypt</a>"]);
$messageReload      = $l->t('If you think your server has HTTPS enabled, you can try to %s with HTTPS.', ["<a id=\"pw-link-https\" href=\"?\">{$reloadPage}</a>"]);
?>
<div id="main">
    <div class="passwords-browser-compatibility">
        <h1 class="title"><?php p($title);?></h1>
        <div class="message">
            <b><?php p($messageHead); ?></b>
            <br><br><?php p($messageConfigure); ?>
            <?php p($messageCertificate); ?>
            <br><br><?php p($messageReload); ?>
        </div>
    </div>
</div>