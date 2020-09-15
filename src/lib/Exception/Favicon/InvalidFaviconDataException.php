<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception\Favicon;

use Exception;
use Throwable;

/**
 * Class InvalidFaviconDataException
 *
 * @package OCA\Passwords\Exception\Favicon
 */
class InvalidFaviconDataException extends Exception {

    const EXCEPTION_CODE    = 101;
    const EXCEPTION_MESSAGE = 'Favicon service returned unsupported data type: ';

    /**
     * InvalidFaviconDataException constructor.
     *
     * @param string          $mime
     * @param Throwable|null $previous
     */
    public function __construct(string $mime, Throwable $previous = null) {
        parent::__construct(static::EXCEPTION_MESSAGE.$mime, static::EXCEPTION_CODE, $previous);
    }
}