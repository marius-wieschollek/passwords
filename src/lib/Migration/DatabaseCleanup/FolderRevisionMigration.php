<?php

namespace OCA\Passwords\Migration\DatabaseCleanup;

use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class FolderRevisionRepairHelper
 *
 * @package OCA\Passwords\Helper\Repair
 */
class FolderRevisionMigration extends AbstractRevisionMigration {

    /**
     * @var string
     */
    protected $objectName = 'folder';

    /**
     * FolderRevisionRepairHelper constructor.
     *
     * @param FolderRevisionMapper $revisionMapper
     * @param FolderMapper         $modelMapper
     * @param ConfigurationService $config
     */
    public function __construct(FolderRevisionMapper $revisionMapper, FolderMapper $modelMapper, ConfigurationService $config) {
        parent::__construct($revisionMapper, $modelMapper, $config);
    }
}