<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks\Manager;

use OC\Hooks\BasicEmitter;

/**
 * Class HookManager
 *
 * @package OCA\Passwords\Hooks\Manager
 */
class HookManager extends BasicEmitter {

    /**
     * @param string $scope
     * @param string $method
     * @param array  $arguments
     */
    public function emit($scope, $method, array $arguments = []): void {
        parent::emit($scope, $method, $arguments);
    }
}