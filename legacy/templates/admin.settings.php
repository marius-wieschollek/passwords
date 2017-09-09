<?php

namespace OCA\Passwords;

\OCP\Util::addStyle('passwords', 'settings');
\OCP\Util::addScript('passwords', 'settings');

$tmpl = new \OCP\Template('passwords', 'part.admin');

return $tmpl->fetchPage();
