<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

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

    /**
     * @param FolderRevision|RevisionInterface $revision
     *
     * @return bool
     * @throws \Exception
     */
    public function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;

        if($revision->getParent() !== FolderService::BASE_FOLDER_UUID) {
            try {
                $this->modelMapper->findByUuid($revision->getParent());
            } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
                $revision->setParent(FolderService::BASE_FOLDER_UUID);
                $fixed = true;
            }
        }

        if($fixed) $this->revisionService->save($revision);

        return $fixed || parent::repairRevision($revision);
    }
}