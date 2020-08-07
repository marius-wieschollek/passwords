<?php

namespace OCA\Passwords\Exception;

abstract class AbstractException extends \Exception {

    const EXCEPTION_CODE = 0;

    public function __construct(\Throwable $previous = null) {
        parent::__construct(static::EXCEPTION_MESSAGE, static::EXCEPTION_CODE, $previous);
    }

}