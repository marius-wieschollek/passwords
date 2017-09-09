<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

#script('passwords', 'admin');         // adds a Javascript file
style('passwords', 'admin');    // adds a CSS file

$tmpl = new \OCP\Template('passwords', 'admin/settings');

return $tmpl->fetchPage();
