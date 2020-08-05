<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;

/**
 * Class FolderObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class FolderObjectHelper extends AbstractObjectHelper {

    const LEVEL_TAGS          = 'tags';
    const LEVEL_PARENT        = 'parent';
    const LEVEL_FOLDERS       = 'folders';
    const LEVEL_FOLDER_IDS    = 'folder-ids';
    const LEVEL_PASSWORDS     = 'passwords';
    const LEVEL_PASSWORD_IDS  = 'password-ids';
    const LEVEL_PASSWORD_TAGS = 'password-tags';

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
     * @param EntityInterface|Folder $folder
     * @param string                 $level
     * @param array                  $filter
     *
     * @return array|null
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    public function getApiObject(
        EntityInterface $folder,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        $detailLevel = explode('+', $level);
        $withModel   = in_array(self::LEVEL_MODEL, $detailLevel);

        /** @var FolderRevision $revision */
        $revision = $this->getRevision($folder, $filter, $withModel);
        if($revision === null) return null;

        if($withModel) {
            $object = $this->getModel($folder, $revision);
        } else {
            $object = ['id' => $folder->getUuid()];
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($folder, $object);
        }
        if(in_array(self::LEVEL_PARENT, $detailLevel)) {
            $object = $this->getParent($revision, $object);
        }
        if(in_array(self::LEVEL_FOLDERS, $detailLevel)) {
            $object = $this->getFolders($revision, $object);
        } else if(in_array(self::LEVEL_FOLDER_IDS, $detailLevel)) {
            $object = $this->getFolders($revision, $object, false);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $includeTags = in_array(self::LEVEL_PASSWORD_TAGS, $detailLevel);
            $object      = $this->getPasswords($revision, $object, $includeTags);
        } else if(in_array(self::LEVEL_PASSWORD_IDS, $detailLevel)) {
            $object = $this->getPasswords($revision, $object, false, false);
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
            'id'       => $folder->getUuid(),
            'created'  => $folder->getCreated(),
            'updated'  => $folder->getUpdated(),
            'edited'   => $revision->getEdited(),
            'revision' => $revision->getUuid(),
            'label'    => $revision->getLabel(),
            'parent'   => $revision->getParent(),
            'cseKey'   => $revision->getCseKey(),
            'cseType'  => $revision->getCseType(),
            'sseType'  => $revision->getSseType(),
            'hidden'   => $revision->isHidden(),
            'trashed'  => $revision->isTrashed(),
            'favorite' => $revision->isFavorite(),
            'client'   => $revision->getClient()
        ];
    }

    /**
     * @param Folder $folder
     * @param array  $object
     *
     * @return array
     * @throws Exception
     */
    protected function getRevisions(Folder $folder, array $object): array {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($folder->getUuid(), true);

        $object['revisions'] = [];
        foreach($revisions as $revision) {
            $current = [
                'id'       => $revision->getUuid(),
                'created'  => $revision->getCreated(),
                'updated'  => $revision->getUpdated(),
                'edited'   => $revision->getEdited(),
                'label'    => $revision->getLabel(),
                'parent'   => $revision->getParent(),
                'cseKey'   => $revision->getCseKey(),
                'cseType'  => $revision->getCseType(),
                'sseType'  => $revision->getSseType(),
                'hidden'   => $revision->isHidden(),
                'trashed'  => $revision->isTrashed(),
                'favorite' => $revision->isFavorite(),
                'client'   => $revision->getClient()
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
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    protected function getParent(FolderRevision $revision, array $object): array {

        $filters = $revision->isHidden() ? []:['hidden' => false];
        $parent  = $this->folderService->findByUuid($revision->getParent());
        $obj     = $this->getApiObject($parent, self::LEVEL_MODEL, $filters);

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
     * @param bool           $includeModels
     *
     * @return array
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    protected function getFolders(FolderRevision $revision, array $object, bool $includeModels = true): array {

        $filters = ['trashed' => false];
        if(!$revision->isHidden()) $filters['hidden'] = false;

        $object['folders'] = [];
        $folders           = $this->folderService->findByParent($revision->getModel());

        foreach($folders as $folder) {
            if($includeModels) {
                $obj = $this->getApiObject($folder, self::LEVEL_MODEL, $filters);

                if($obj !== null) $object['folders'][] = $obj;
            } else if($this->matchesFilter($folder, $filters)) {
                $object['folders'][] = $folder->getUuid();
            }
        }

        return $object;
    }

    /**
     * @param FolderRevision $revision
     * @param array          $object
     * @param bool           $includeTags
     * @param bool           $includeModels
     *
     * @return array
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    protected function getPasswords(FolderRevision $revision, array $object, bool $includeTags = false, bool $includeModels = true): array {

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $object['passwords'] = [];
        $objectHelper        = $this->getPasswordObjectHelper();
        $passwords           = $this->passwordService->findByFolder($revision->getModel());

        $detailLevel = $includeTags ? self::LEVEL_MODEL.'+'.self::LEVEL_TAGS:self::LEVEL_MODEL;
        foreach($passwords as $password) {
            if($includeModels) {
                $obj = $objectHelper->getApiObject($password, $detailLevel, $filters);

                if($obj !== null) $object['passwords'][] = $obj;
            } else if($objectHelper->matchesFilter($password, $filters)) {
                $object['passwords'][] = $password->getUuid();
            }
        }

        return $object;
    }

    /**
     * @return PasswordObjectHelper
     * @throws QueryException
     */
    protected function getPasswordObjectHelper(): PasswordObjectHelper {
        if(!$this->passwordObjectHelper) {
            $this->passwordObjectHelper = $this->container->query(PasswordObjectHelper::class);
        }

        return $this->passwordObjectHelper;
    }
}