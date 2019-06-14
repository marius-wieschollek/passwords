<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks\Manager;

use OC\Hooks\BasicEmitter;
use OCA\Passwords\Hooks\ChallengeHook;
use OCA\Passwords\Hooks\FolderHook;
use OCA\Passwords\Hooks\PasswordHook;
use OCA\Passwords\Hooks\ShareHook;
use OCA\Passwords\Hooks\TagHook;
use OCA\Passwords\Hooks\UserHook;
use OCP\AppFramework\IAppContainer;

/**
 * Class HookManager
 *
 * @package OCA\Passwords\Hooks\Manager
 */
class HookManager extends BasicEmitter {

    /**
     * @var IAppContainer
     */
    protected $container;

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
     */
    public function emit($scope, $method, array $arguments = []): void {
        parent::emit($scope, $method, $arguments);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \Exception
     */
    public function __call($name, $arguments): void {
        preg_match("/([a-z]+)([a-zA-Z]+)/", $name, $matches);
        $scope = $matches[1];
        $method = lcfirst($matches[2]);
        $class = null;

        switch($scope) {
            case 'folder':
                $class = FolderHook::class;
                break;
            case 'password':
                $class = PasswordHook::class;
                break;
            case 'tag':
                $class = TagHook::class;
                break;
            case 'share':
                $class = ShareHook::class;
                break;
            case 'user':
                $class = UserHook::class;
                break;
            case 'challenge':
                $class = ChallengeHook::class;
                break;
        }

        if($class === null) throw new \Exception("Invalid hook scope {$scope} in {$name}");
        $object = $this->container->query($class);
        if(!method_exists($object, $method)) throw new \Exception("Invalid hook method {$method} in {$name}");

        $object->{$method}(...$arguments);
    }
}