<?php

namespace OCA\Passwords\Exception\Favicon;

use OCA\Passwords\Exception\AbstractException;

class FaviconRequestException extends AbstractException {
    const EXCEPTION_CODE    = 103;
    const EXCEPTION_MESSAGE = 'The request to the favicon service failed';
}