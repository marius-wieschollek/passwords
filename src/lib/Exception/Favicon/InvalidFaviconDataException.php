<?php

namespace OCA\Passwords\Exception\Favicon;

use OCA\Passwords\Exception\AbstractException;
use Throwable;

class InvalidFaviconDataException extends \Exception {

    const EXCEPTION_CODE = 101;
    const EXCEPTION_MESSAGE = 'Favicon service returned unsupported data type: ';

    public function __construct(string $mime, \Throwable $previous = null) {
        parent::__construct(static::EXCEPTION_MESSAGE.$mime, static::EXCEPTION_CODE, $previous);
    }
}