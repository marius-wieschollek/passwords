<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

#script('passwords', 'personal');         // adds a Javascript file
style('passwords', 'personal');    // adds a CSS file

$tmpl = new \OCP\Template('passwords', 'personal/settings');

return $tmpl->fetchPage();
