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

namespace OCA\Passwords\Exception\Image\Imaginary;

use Throwable;

class ImaginaryCommunicationException extends \Exception {

    const EXCEPTION_CODE = 0;

    /**
     * AbstractException constructor.
     *
     * @param Throwable|null $previous
     */
    public function __construct(string $message, Throwable $previous = null) {
        parent::__construct($message, static::EXCEPTION_CODE, $previous);
    }
}