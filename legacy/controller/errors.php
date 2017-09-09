<?php

namespace OCA\Passwords\Controller;

use Closure;

use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\DataResponse;

use OCA\Passwords\Service\NotFoundException;


trait Errors {

    protected function handleNotFound (Closure $callback) {
        try {
            return new DataResponse($callback());
        } catch(NotFoundException $e) {
            $message = ['message' => $e->getMessage()];
            return new DataResponse($message, Http::STATUS_NOT_FOUND);
        }
    }

}
