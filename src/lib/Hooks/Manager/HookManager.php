<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 23:17
 */

namespace OCA\Passwords\Hooks\Manager;

use OC\Hooks\BasicEmitter;

/**
 * Class HookManager
 *
 * @package OCA\Passwords\Hooks\Manager
 */
class HookManager extends BasicEmitter {

    public function emit($scope, $method, array $arguments = array()): void {
        parent::emit($scope, $method, $arguments);
    }
}