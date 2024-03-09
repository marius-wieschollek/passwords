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

use OCA\Passwords\Exception\AbstractException;

class NotConfiguredException extends AbstractException {

    const EXCEPTION_MESSAGE = 'Imaginary image provider is enabled but not configured';

}