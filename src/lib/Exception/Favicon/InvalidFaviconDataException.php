<?php

namespace OCA\Passwords\Exception\Favicon;

use OCA\Passwords\Exception\AbstractException;
use Throwable;

class InvalidFaviconDataException extends AbstractException {

    const EXCEPTION_CODE = 101;
    const EXCEPTION_MESSAGE = 'Favicon service returned unsupported data type';
}