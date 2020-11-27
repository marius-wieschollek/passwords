<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks\Manager;

use Exception;
use OC\Hooks\BasicEmitter;
use OCP\AppFramework\IAppContainer;

/**
 * Class HookManager
 *
 * @package OCA\Passwords\Hooks\Manager
 * @deprecated
 */
class HookManager extends BasicEmitter {

    /**
     * @var IAppContainer
     */
    protected IAppContainer $container;

    /**
     * HookManager constructor.
     *
     * @param IAppContainer $container
     */
    public function __construct(IAppContainer $container) {
        $this->container = $container;
    }

    /**
     * @param string $scope
     * @param string $method
     * @param array  $arguments
     * @deprecated
     */
    public function emit($scope, $method, array $arguments = []): void {
        parent::emit($scope, $method, $arguments);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws Exception
     * @deprecated
     */
    public function __call($name, $arguments): void {
        preg_match("/([a-z]+)([a-zA-Z]+)/", $name, $matches);
        $scope = $matches[1];
        $method = lcfirst($matches[2]);
        $class = null;
        if($class === null) throw new Exception("Invalid hook scope {$scope} in {$name}");
        $object = $this->container->query($class);
        if(!method_exists($object, $method)) throw new Exception("Invalid hook method {$method} in {$name}");

        $object->{$method}(...$arguments);
    }
}