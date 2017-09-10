<?php
/** @var $l \OCP\IL10N */

use OCA\Passwords\AppInfo\Application;

/** @var $_ array */

script(Application::APP_NAME, ['utility', 'admin']);
style(Application::APP_NAME, 'passwords-admin');

print_unescaped($this->inc('admin/settings'));