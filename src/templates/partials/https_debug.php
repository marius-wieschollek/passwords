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


$headers = $config->getSystemValue('forwarded_for_headers', ['HTTP_X_FORWARDED_FOR']);
$isProxy = isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || isset($_SERVER['HTTP_X_FORWARDED_PORT']);

$expected = [
        'remote_address' => $_SERVER['REMOTE_ADDR'],
        'overwriteprotocol' => 'not set',
        'overwritecondaddr' => 'not set',
        'trusted_proxies' => '[]',
        'forwarded_proto' => 'not set',
        'forwarded_for' => 'not set',
        'proxy' => 'Yes if Proxy'
];

foreach($headers as $header) {
    if(isset($_SERVER[$header])) {
        $expected['remote_address'] = $_SERVER[$header];
        $isProxy = true;
    }
}

if($isProxy) {
    $expected['proxy'] = '';
    $expected['forwarded_proto'] = 'https';
    $expected['forwarded_for'] = $expected['remote_address'];
    $expected['overwriteprotocol'] = 'https';

    if($expected['remote_address'] !== $_SERVER['REMOTE_ADDR']) {
        $expected['overwritecondaddr'] = '^'.str_replace('.', '\.', $_SERVER['REMOTE_ADDR']).'$';
        $expected['trusted_proxies'] = "[\"{$_SERVER['REMOTE_ADDR']}\"]";
    } else {
        $expected['overwritecondaddr'] = '^the\.proxy\.ip$';
        $expected['trusted_proxies'] = '["the.proxy.ip"]';
    }
}

?>

<div class="message report">
    <b><span class="fa fa-shield">&nbsp; &nbsp;</span>HTTPS Setup Report</b>
    <br><br>
    <table>
        <tr>
            <th>HTTPS detection</th>
            <th>Actual</th>
            <th>Expected</th>
        </tr>
        <tr>
            <td>Nextcloud reported protocol</td>
            <td><?=$request->getServerProtocol()?></td>
            <td>https</td>
        </tr>
        <tr>
            <td>Client reported protocol</td>
            <td><?php p($request->getParam('https', 'true') === 'true' ? 'https':'http') ?></td>
            <td>https</td>
        </tr>
        <tr>
            <th>Proxy detection</th>
            <th>Actual</th>
            <th>Expected</th>
        </tr>
        <tr>
            <td>Proxy detected</td>
            <td><?php p($isProxy ? 'yes':'no')?></td>
            <td><?php p($expected['proxy']) ?></td>
        </tr>
        <tr>
            <td>Detected remote address</td>
            <td><?=$request->getRemoteAddress()?></td>
            <td><?php p($expected['remote_address']) ?></td>
        </tr>
        <tr>
            <th>Nextcloud Proxy Settings</th>
            <th>Actual</th>
            <th>Expected</th>
        </tr>
        <tr>
            <td>overwriteprotocol</td>
            <td><?=$config->getSystemValue('overwriteprotocol', 'not set')?></td>
            <td><?php p($expected['overwriteprotocol']) ?></td>
        </tr>
        <tr>
            <td>overwritecondaddr</td>
            <td><?=$config->getSystemValue('overwritecondaddr', 'not set')?></td>
            <td><?php p($expected['overwritecondaddr']) ?></td>
        </tr>
        <tr>
            <td>trusted_proxies</td>
            <td><?=json_encode($config->getSystemValue('trusted_proxies', []))?></td>
            <td><?php p($expected['trusted_proxies']) ?></td>
        </tr>
        <tr>
            <th>PHP Variables</th>
            <th>Actual</th>
            <th>Expected</th>
        </tr>
        <tr>
            <td>$_SERVER['HTTPS']</td>
            <td><?php p(isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS']:'not set') ?></td>
            <td>on</td>
        </tr>
        <tr>
            <td>$_SERVER['REQUEST_SCHEME']</td>
            <td><?php p(isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME']:'not set') ?></td>
            <td>https</td>
        </tr>
        <tr>
            <td>$_SERVER['REMOTE_ADDR']</td>
            <td><?php p(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']:'not set') ?></td>
            <td></td>
        </tr>
        <tr>
            <td>$_SERVER['HTTP_X_FORWARDED_PROTO']</td>
            <td><?php p(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO']:'not set') ?></td>
            <td><?php p($expected['forwarded_proto']) ?></td>
        </tr>
        <?php
        foreach($headers as $header): ?>
            <tr>
                <td>$_SERVER['<?=$header?>']</td>
                <td><?php p(isset($_SERVER[$header]) ? $_SERVER[$header]:'not set') ?></td>
                <td><?php p($expected['forwarded_for']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>