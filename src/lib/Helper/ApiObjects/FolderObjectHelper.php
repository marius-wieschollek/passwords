<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:45
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCP\AppFramework\IAppContainer;

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
     * @param IAppContainer         $container
     * @param FolderService         $folderService
     * @param PasswordService       $passwordService
     * @param EncryptionService     $encryptionService
     * @param FolderRevisionService $folderRevisionService
     */
    public function __construct(
        IAppContainer $container,
        FolderService $folderService,
        PasswordService $passwordService,
        EncryptionService $encryptionService,
        FolderRevisionService $folderRevisionService
    ) {
        parent::__construct($container, $encryptionService, $folderRevisionService);

        $this->folderService   = $folderService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param ModelInterface|Folder $folder
     * @param string                $level
     * @param array                 $filter
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getApiObject(
        ModelInterface $folder,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        /** @var FolderRevision $revision */
        $revision = $this->getRevision($folder, $filter);
        if($revision === null) return null;

        $detailLevel = explode('+', $level);
        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($folder, $revision);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($folder, $object);
        }
        if(in_array(self::LEVEL_PARENT, $detailLevel)) {
            $object = $this->getParent($revision, $object);
        }
        if(in_array(self::LEVEL_FOLDERS, $detailLevel)) {
            $object = $this->getFolders($revision, $object);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($revision, $object);
        }

        return $object;
    }

    /**
     * @param Folder         $folder
     *
     * @param FolderRevision $revision
     *
     * @return array
     */
    protected function getModel(Folder $folder, FolderRevision $revision): array {

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
     * @throws \Exception
     */
    protected function getRevisions(Folder $folder, array $object): array {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($folder->getUuid(), true);

        $object['revisions'] = [];
        foreach ($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
                'owner'     => $revision->getUserId(),
                'created'   => $revision->getCreated(),
                'updated'   => $revision->getUpdated(),
                'label'     => $revision->getLabel(),
                'parent'    => $revision->getParent(),
                'cseType'   => $revision->getCseType(),
                'sseType'   => $revision->getSseType(),
                'hidden'    => $revision->isHidden(),
                'trashed'   => $revision->isTrashed(),
                'favourite' => $revision->isFavourite()
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getParent(FolderRevision $revision, array $object): array {

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;
        $parent = $this->folderService->findByUuid($revision->getParent());
        $obj    = $this->getApiObject($parent, self::LEVEL_MODEL, $filters);

        if($obj !== null) {
            $object['parent'] = $obj;
        } else {
            $folder           = $this->folderService->getBaseFolder();
            $object['parent'] = $this->getApiObject($folder);
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getFolders(FolderRevision $revision, array $object): array {

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $object['folders'] = [];
        $folders           = $this->folderService->findByParent($revision->getModel());

        foreach ($folders as $folder) {
            $obj = $this->getApiObject($folder, self::LEVEL_MODEL, $filters);

            if($obj !== null) $object['folders'][] = $obj;
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     *
     * @return array
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswords(FolderRevision $revision, array $object): array {

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $object['passwords'] = [];
        $objectHelper        = $this->getPasswordObjectHelper();
        $passwords           = $this->passwordService->findByFolder($revision->getModel());

        foreach ($passwords as $password) {
            $obj = $objectHelper->getApiObject($password, self::LEVEL_MODEL, $filters);

            if($obj !== null) $object['passwords'][] = $obj;
        }

        return $object;
    }

    /**
     * @return PasswordObjectHelper
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswordObjectHelper(): PasswordObjectHelper {
        if(!$this->passwordObjectHelper) {
            $this->passwordObjectHelper = $this->container->query('PasswordObjectHelper');
        }

        return $this->passwordObjectHelper;
    }
}