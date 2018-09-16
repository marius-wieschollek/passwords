<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 *
 * @var $l \OCP\IL10N
 * @var $_ array
 */

$uid = \OC::$server->getUserSession()->getUser()->getUID();
if(!\OC_User::isAdminUser($uid)) return;

$config  = \OC::$server->getConfig();
$request = \OC::$server->getRequest();
?>

<div id="passwords-https-detection-details">

    <table>
        <tr>
            <th colspan="2">&nbsp; &nbsp;<span class="fa fa-shield">&nbsp; &nbsp;</span>Passwords HTTPS Detection Details</th>
        </tr>
        <tr>
            <td>Passwords detects HTTPS enabled</td>
            <td><?php p($_['https'] ? 'true':'false') ?></td>
        </tr>
        <tr>
            <td>Client reports HTTPS enabled</td>
            <td><?php p($request->getParam('https', 'true')) ?></td>
        </tr>
        <tr>
            <td>Nextcloud detected protocol</td>
            <td><?=$request->getServerProtocol()?></td>
        </tr>
        <tr>
            <td>Nextcloud overwriteprotocol</td>
            <td><?=$config->getSystemValue('overwriteprotocol', 'not set')?></td>
        </tr>
        <tr>
            <td>Nextcloud overwritecondaddr</td>
            <td><?=$config->getSystemValue('overwritecondaddr', 'not set')?></td>
        </tr>
        <tr>
            <td>Nextcloud trusted_proxies</td>
            <td><?=json_encode($config->getSystemValue('trusted_proxies', []))?></td>
        </tr>
        <tr>
            <td>Nextcloud detected remote address</td>
            <td><?=$request->getRemoteAddress()?></td>
        </tr>
        <tr>
            <td>PHP $_SERVER['HTTPS']</td>
            <td><?php p(isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS']:'not set') ?></td>
        </tr>
        <tr>
            <td>PHP $_SERVER['REQUEST_SCHEME']</td>
            <td><?php p(isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME']:'not set') ?></td>
        </tr>
        <tr>
            <td>PHP $_SERVER['REMOTE_ADDR']</td>
            <td><?php p(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']:'not set') ?></td>
        </tr>
        <tr>
            <td>PHP $_SERVER['HTTP_X_FORWARDED_PROTO']</td>
            <td><?php p(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO']:'not set') ?></td>
        </tr>
        <?php
        $headers = $config->getSystemValue('forwarded_for_headers', ['HTTP_X_FORWARDED_FOR']);
        foreach($headers as $header): ?>
            <tr>
                <td>PHP $_SERVER['<?=$header?>']</td>
                <td><?php p(isset($_SERVER[$header]) ? $_SERVER[$header]:'not set') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<style>
    #passwords-https-detection-details {
        position      : fixed;
        background    : <?=\OC::$server->getThemingDefaults()->getColorPrimary()?>;
        color         : <?=\OC::$server->getThemingDefaults()->getTextColorPrimary()?>;
        z-index       : 10000;
        border-radius : 3px;
        right         : 20px;
        bottom        : 20px;
        padding       : 3px 0;
        max-height    : 1.65rem;
        overflow      : hidden;
        transition    : max-height 0.25s ease-in-out;
    }

    #passwords-https-detection-details:hover {
        max-height : 20rem;
    }

    #passwords-https-detection-details tr:hover {
        color      : <?=\OC::$server->getThemingDefaults()->getColorPrimary()?>;
        background : <?=\OC::$server->getThemingDefaults()->getTextColorPrimary()?>;
    }

    #passwords-https-detection-details th {
        font-weight : bold;
        padding     : 0 3px;
    }

    #passwords-https-detection-details td {
        font-family : monospace;
        cursor      : text;
        padding     : 0 3px;
    }
</style>