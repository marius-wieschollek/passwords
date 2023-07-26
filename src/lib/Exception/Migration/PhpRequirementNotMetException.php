<?php
/*
 * @copyright 2021 Passwords App
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
 * Class PhpRequirementNotMetException
 *
 * @package OCA\Passwords\Exception\Migration
 */
class PhpRequirementNotMetException extends Exception {

    /**
     * PhpRequirementNotMetException constructor.
     *
     * @param                $currentVersion
     * @param                $minimumVersion
     * @param Throwable|null $previous
     */
    public function __construct($currentVersion, $minimumVersion, Throwable $previous = null) {
        parent::__construct(
            "You are using PHP {$currentVersion} but this release of the passwords app requires at least PHP {$minimumVersion}. See https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/Migrations/Minimum-Requirement-Error for help.",
            E_USER_ERROR,
            $previous
        );
    }
}