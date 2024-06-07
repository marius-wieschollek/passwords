<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Exception\Image;

use Exception;
use Throwable;

class ImageExportException extends Exception {

    const EXCEPTION_CODE = 0;

    /**
     * AbstractException constructor.
     *
     * @param Throwable|null $previous
     */
    public function __construct(string $format, Throwable $previous = null) {
        parent::__construct("Could not convert image to {$format}", static::EXCEPTION_CODE, $previous);
    }
}