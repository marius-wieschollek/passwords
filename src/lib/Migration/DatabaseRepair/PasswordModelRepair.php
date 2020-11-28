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

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;

/**
 * Class PasswordModelRepair
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class PasswordModelRepair extends AbstractModelRepair {

    /**
     * @var string
     */
    protected string $objectName = 'password';

    /**
     * PasswordModelRepair constructor.
     *
     * @param PasswordService         $modelService
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(PasswordService $modelService, PasswordRevisionService $revisionService) {
        parent::__construct($modelService, $revisionService);
    }
}