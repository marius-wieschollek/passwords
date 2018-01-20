<?php
/** @var $l \OCP\IL10N */

use OCA\Passwords\AppInfo\Application;

/** @var $_ array */

script('passwords', ['Static/utility', 'Static/common', 'Static/admin']);
style(Application::APP_NAME, 'admin');

print_unescaped($this->inc('admin/settings'));