<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 22.12.17
 * Time: 23:18
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\Object\FolderRevisionService;

/**
 * Class FolderRevisionHook
 *
 * @package OCA\Passwords\Hooks
 */
class FolderRevisionHook {

    /**
     * @var FolderRevisionService
     */
    protected $revisionService;

    /**
     * FolderRevisionHook constructor.
     *
     * @param FolderRevisionService $revisionService
     */
    public function __construct(FolderRevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param FolderRevision $originalRevision
     * @param FolderRevision $clonedRevision
     *
     * @TODO check if trashed and suspend passwords
     */
    public function postClone(FolderRevision $originalRevision, FolderRevision $clonedRevision): void {
    }
}