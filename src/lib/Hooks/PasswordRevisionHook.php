<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 31.12.17
 * Time: 01:20
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\PasswordRevision;

class PasswordRevisionHook {

    public function preSave(PasswordRevision $revision) {
        $revision;
    }
}