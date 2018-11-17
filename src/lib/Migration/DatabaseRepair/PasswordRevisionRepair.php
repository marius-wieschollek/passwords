<?php

namespace OCA\Passwords\Migration\DatabaseRepair;

use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Services\EncryptionService;
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
     * @var EncryptionService
     */
    protected $encryptionService;

    /**
     * @var string
     */
    protected $objectName = 'password';

    /**
     * PasswordRevisionRepair constructor.
     *
     * @param PasswordMapper          $modelMapper
     * @param PasswordRevisionService $revisionService
     * @param EncryptionService       $encryptionService
     * @param FolderMapper            $folderMapper
     */
    public function __construct(
        FolderMapper $folderMapper,
        PasswordMapper $modelMapper,
        EncryptionService $encryptionService,
        PasswordRevisionService $revisionService
    ) {
        parent::__construct($modelMapper, $revisionService);
        $this->folderMapper      = $folderMapper;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param PasswordRevision|RevisionInterface $revision
     *
     * @return bool
     * @throws \Exception
     */
    public function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;

        if($revision->_isDecrypted() === false && $revision->getCustomFields() === '{}') {
            $revision->setCustomFields(null);
            $fixed = true;
        }

        if($revision->getCustomFields() === null && $revision->getCseType() === 'none') {
            $this->encryptionService->decrypt($revision);
            $revision->setCustomFields('{}');
            $fixed = true;
        }

        if($revision->getStatus() === 1 && $revision->getStatusCode() === AbstractSecurityCheckHelper::STATUS_GOOD) {
            $revision->setStatus(0);
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