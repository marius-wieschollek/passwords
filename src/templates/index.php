<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 *
 * @var $_ array
 */

use OCA\Passwords\AppInfo\Application;
use OCP\Util;

if($_['https']) {
    style(Application::APP_NAME, ['app']);
    Util::addScript(Application::APP_NAME, 'Static/compatibility');
    Util::addScript(Application::APP_NAME, 'Static/utility');
    Util::addScript(Application::APP_NAME, 'Static/app'.(isset($_['hash']) ? '.'.$_['hash']:''));
    ?>
    <div id="main" class="loading"></div>
<?php } else {
    print_unescaped($this->inc('partials/https'));
} ?>