<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
    protected $objectName = 'password';

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