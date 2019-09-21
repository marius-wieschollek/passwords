<?php

namespace OCP\AppFramework;

use OCP\Route\IRouter;

class App {

    public static function buildAppNamespace(string $appId, string $topNamespace = 'OCA\\'): string {
        return 'OCA\\Passwords\\';
    }

    public function __construct(string $appName, array $urlParams = []) {
    }

    public function getContainer(): IAppContainer {
        return new IAppContainer();
    }

    public function registerRoutes(IRouter $router, array $routes) {
    }

    public function dispatch(string $controllerName, string $methodName) {
    }
}
