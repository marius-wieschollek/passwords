<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception;

use Exception;
use Throwable;

/**
 * Class AbstractException
 *
 * @package OCA\Passwords\Exception
 */
abstract class AbstractException extends Exception {

    const EXCEPTION_CODE = 0;

    /**
     * AbstractException constructor.
     *
     * @param Throwable|null $previous
     */
    public function __construct(Throwable $previous = null) {
        parent::__construct(static::EXCEPTION_MESSAGE, static::EXCEPTION_CODE, $previous);
    }

}