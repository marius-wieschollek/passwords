<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception\Favicon;

use OCA\Passwords\Exception\AbstractException;

/**
 * Class NoFaviconDataException
 *
 * @package OCA\Passwords\Exception\Favicon
 */
class NoFaviconDataException extends AbstractException {
    const EXCEPTION_CODE    = 100;
    const EXCEPTION_MESSAGE = 'Favicon service returned no data';
}