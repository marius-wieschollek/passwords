<?php

namespace OCA\Passwords\Migration\DatabaseCleanup;

use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class TagRevisionRepairHelper
 *
 * @package OCA\Passwords\Helper\Repair
 */
class TagRevisionMigration extends AbstractRevisionMigration {

    /**
     * @var string
     */
    protected $objectName = 'tag';

    /**
     * FolderRevisionRepairHelper constructor.
     *
     * @param TagRevisionMapper    $revisionMapper
     * @param TagMapper            $modelMapper
     * @param ConfigurationService $config
     */
    public function __construct(TagRevisionMapper $revisionMapper, TagMapper $modelMapper, ConfigurationService $config) {
        parent::__construct($revisionMapper, $modelMapper, $config);
    }
}