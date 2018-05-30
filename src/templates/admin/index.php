<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 *
 * @var $l \OCP\IL10N
 * @var $_ array
 */

use OCA\Passwords\AppInfo\Application;

script('passwords', ['Static/utility', 'Static/common', 'Static/admin']);
style(Application::APP_NAME, 'admin');

if($_['debugHTTPS']) {
    $_['https'] = $_['support']['https'];
    print_unescaped($this->inc('partials/https_debug', $_));
}

print_unescaped($this->inc('admin/settings'));