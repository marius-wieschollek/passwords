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

use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;

/**
 * Class FolderModelRepair
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class FolderModelRepair extends AbstractModelRepair {

    /**
     * @var string
     */
    protected string $objectName = 'folder';

    /**
     * FolderModelRepair constructor.
     *
     * @param FolderService         $modelService
     * @param FolderRevisionService $revisionService
     */
    public function __construct(FolderService $modelService, FolderRevisionService $revisionService) {
        parent::__construct($modelService, $revisionService);
    }
}