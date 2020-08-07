<?php

namespace OCA\Passwords\Exception\Favicon;

use OCA\Passwords\Exception\AbstractException;

class NoFaviconDataException extends AbstractException {
    const EXCEPTION_CODE    = 100;
    const EXCEPTION_MESSAGE = 'Favicon service returned no data';
}