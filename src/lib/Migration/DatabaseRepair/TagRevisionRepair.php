<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\TagRevisionService;

/**
 * Class TagRevisionRepairHelper
 *
 * @package OCA\Passwords\Helper\Repair
 */
class TagRevisionRepair extends AbstractRevisionRepair {

    /**
     * @var string
     */
    protected $objectName = 'tag';

    /**
     * TagRevisionRepair constructor.
     *
     * @param TagMapper          $modelMapper
     * @param TagRevisionService $revisionService
     * @param EncryptionService  $encryption
     * @param EnvironmentService $environment
     */
    public function __construct(TagMapper $modelMapper, TagRevisionService $revisionService, EncryptionService $encryption, EnvironmentService $environment) {
        parent::__construct($modelMapper, $revisionService, $encryption, $environment);
    }
}