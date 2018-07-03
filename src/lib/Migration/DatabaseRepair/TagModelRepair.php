<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;

/**
 * Class TagModelRepair
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class TagModelRepair extends AbstractModelRepair {

    /**
     * @var string
     */
    protected $objectName = 'tag';

    /**
     * TagModelRepair constructor.
     *
     * @param TagService         $modelService
     * @param TagRevisionService $revisionService
     */
    public function __construct(TagService $modelService, TagRevisionService $revisionService) {
        parent::__construct($modelService, $revisionService);
    }
}