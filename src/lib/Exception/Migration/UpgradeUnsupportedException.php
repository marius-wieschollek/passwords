<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Exception\Migration;

use Exception;
use Throwable;

/**
 * Class UpgradeUnsupportedException
 *
 * @package OCA\Passwords\Exception\Migration
 */
class UpgradeUnsupportedException extends Exception {

    /**
     * UpgradeUnsupportedException constructor.
     *
     * @param                $previousVersion
     * @param                $minimumVersion
     * @param Throwable|null $previous
     */
    public function __construct($previousVersion, $minimumVersion, Throwable $previous = null) {
        parent::__construct(
            "Upgrading the passwords app from {$previousVersion} is no longer supported. Please consult the app manual in order to upgrade to {$minimumVersion} before installing this upgrade.",
            E_USER_ERROR,
            $previous
        );
    }
}