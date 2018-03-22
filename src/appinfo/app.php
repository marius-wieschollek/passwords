<?php

namespace OCA\Passwords\AppInfo;

use OCP\AppFramework\QueryException;

try {
    new Application();
} catch(QueryException $e) {
}