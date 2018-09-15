<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\TagMapper;
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
     */
    public function __construct(TagMapper $modelMapper, TagRevisionService $revisionService) {
        parent::__construct($modelMapper, $revisionService);
    }
}