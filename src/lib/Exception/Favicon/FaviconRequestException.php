<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception\Favicon;

use OCA\Passwords\Exception\AbstractException;

/**
 * Class FaviconRequestException
 *
 * @package OCA\Passwords\Exception\Favicon
 */
class FaviconRequestException extends AbstractException {
    const EXCEPTION_CODE    = 103;
    const EXCEPTION_MESSAGE = 'The request to the favicon service failed';
}