<?php

namespace OCA\Passwords\AppInfo;

use OCP\AppFramework\QueryException;

try {
    new Application();
} catch(QueryException $e) {
    \OC::$server->getLogger()->logException($e, ['app' => Application::APP_NAME]);
}