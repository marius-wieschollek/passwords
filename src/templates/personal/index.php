<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

script('passwords', ['Static/common', 'Static/personal']);
style('passwords', 'personal');

$tmpl = new \OCP\Template('passwords', 'personal/settings');

return $tmpl->fetchPage();
