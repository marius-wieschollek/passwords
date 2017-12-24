<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:45
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;

/**
 * Class FolderObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class FolderObjectHelper extends AbstractObjectHelper {

    const LEVEL_PARENT    = 'parent';
    const LEVEL_FOLDERS   = 'folders';
    const LEVEL_PASSWORDS = 'passwords';

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var FolderRevisionService
     */
    protected $folderRevisionService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordObjectHelper
     */
    protected $passwordObjectHelper;

    /**
     * @var FolderRevision[]
     */
    protected $revisionCache = [];

    /**
     * FolderObjectHelper constructor.
     *
     * @param FolderService         $folderService
     * @param PasswordService       $passwordService
     * @param PasswordObjectHelper  $passwordObjectHelper
     * @param FolderRevisionService $folderRevisionService
     */
    public function __construct(
        FolderService $folderService,
        PasswordService $passwordService,
        PasswordObjectHelper $passwordObjectHelper,
        FolderRevisionService $folderRevisionService
    ) {
        $this->folderService         = $folderService;
        $this->passwordService       = $passwordService;
        $this->passwordObjectHelper  = $passwordObjectHelper;
        $this->folderRevisionService = $folderRevisionService;
    }

    /**
     * @param AbstractModelEntity|Folder $folder
     * @param string                     $level
     *
     * @return array
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function getApiObject(AbstractModelEntity $folder, string $level = self::LEVEL_MODEL): array {
        $detailLevel = explode('+', $level);

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($folder);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($folder, $object);
        }
        if(in_array(self::LEVEL_PARENT, $detailLevel)) {
            $object = $this->getParent($folder, $object);
        }
        if(in_array(self::LEVEL_FOLDERS, $detailLevel)) {
            $object = $this->getFolders($folder, $object);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($folder, $object);
        }

        return $object;
    }

    /**
     * @param Folder $folder
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getModel(Folder $folder): array {
        $revision = $this->getCurrentFolderRevision($folder);

        return [
            'id'        => $folder->getUuid(),
            'owner'     => $folder->getUserId(),
            'created'   => $folder->getCreated(),
            'updated'   => $folder->getUpdated(),
            'revision'  => $revision->getUuid(),
            'label'     => $revision->getLabel(),
            'parent'    => $revision->getParent(),
            'cseType'   => $revision->getCseType(),
            'sseType'   => $revision->getSseType(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite()
        ];
    }

    /**
     * @param Folder $folder
     * @param array  $object
     *
     * @return array
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    protected function getParent(Folder $folder, array $object): array {

        $revision         = $this->getCurrentFolderRevision($folder);
        $parent           = $this->folderService->findByUuid($revision->getParent());
        $object['parent'] = $this->getApiObject($parent);

        return $object;
    }

    /**
     * @param Folder $parent
     * @param array  $object
     *
     * @return array
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    protected function getFolders(Folder $parent, array $object): array {

        $object['folders'] = [];
        $folders           = $this->folderService->findByParent($parent->getUuid());

        foreach ($folders as $folder) {
            $child = $this->getApiObject($folder);

            if(!$child['hidden'] && !$child['trashed']) $object['folders'][] = $child;
        }

        return $object;
    }

    /**
     * @param Folder $parent
     * @param array  $object
     *
     * @return array
     * @throws \Exception
     */
    protected function getPasswords(Folder $parent, array $object): array {

        $object['passwords'] = [];
        $passwords           = $this->passwordService->findByFolder($parent->getUuid());

        foreach ($passwords as $password) {
            $child = $this->passwordObjectHelper->getApiObject($password);

            if(!$child['hidden'] && !$child['trashed']) $object['passwords'][] = $child;
        }

        return $object;
    }

    /**
     * @param Folder $folder
     *
     * @return FolderRevision
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getCurrentFolderRevision(Folder $folder): FolderRevision {
        $id = $folder->getId();

        if(!isset($this->revisionCache[ $id ])) {
            $this->revisionCache[ $id ] = $this->folderRevisionService->findByUuid($folder->getRevision());
        }

        return $this->revisionCache[ $id ];
    }
}