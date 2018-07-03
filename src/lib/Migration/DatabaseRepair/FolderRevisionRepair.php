<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Services\Object\FolderRevisionService;

/**
 * Class FolderRevisionRepairHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class FolderRevisionRepair extends AbstractRevisionRepair {

    /**
     * @var string
     */
    protected $objectName = 'folder';

    /**
     * FolderRevisionRepair constructor.
     *
     * @param FolderMapper          $modelMapper
     * @param FolderRevisionService $revisionService
     */
    public function __construct(FolderMapper $modelMapper, FolderRevisionService $revisionService) {
        parent::__construct($modelMapper, $revisionService);
    }
}