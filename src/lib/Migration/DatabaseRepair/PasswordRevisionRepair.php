<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class PasswordRevisionRepairHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class PasswordRevisionRepair extends AbstractRevisionRepair {

    /**
     * @var FolderMapper
     */
    protected $folderMapper;

    /**
     * @var string
     */
    protected $objectName = 'password';

    /**
     * PasswordRevisionRepair constructor.
     *
     * @param PasswordMapper          $modelMapper
     * @param PasswordRevisionService $revisionService
     * @param FolderMapper            $folderMapper
     */
    public function __construct(PasswordMapper $modelMapper, PasswordRevisionService $revisionService, FolderMapper $folderMapper) {
        parent::__construct($modelMapper, $revisionService);
        $this->folderMapper = $folderMapper;
    }

    /**
     * @param PasswordRevision|RevisionInterface $revision
     *
     * @return bool
     * @throws \Exception
     */
    public function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;
        if($revision->getCustomFields() === null && $revision->getCseType() === 'none') {
            $revision->setCustomFields('{}');
            $fixed = true;
        }

        if($revision->getFolder() !== FolderService::BASE_FOLDER_UUID) {
            try {
                $this->folderMapper->findByUuid($revision->getFolder());
            } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
                $revision->setFolder(FolderService::BASE_FOLDER_UUID);
                $fixed = true;
            }
        }

        if($fixed) $this->revisionService->save($revision);

        return $fixed || parent::repairRevision($revision);
    }
}