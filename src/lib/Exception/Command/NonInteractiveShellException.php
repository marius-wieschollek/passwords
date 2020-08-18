<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception\Command;

use OCA\Passwords\Exception\AbstractException;

/**
 * Class NonInteractiveShellException
 *
 * @package OCA\Passwords\Exception\Command
 */
class NonInteractiveShellException extends AbstractException {
    const EXCEPTION_CODE    = 107;
    const EXCEPTION_MESSAGE = 'Interactive command requires "--no-interaction" to be set to run in non-interactive shell';
}