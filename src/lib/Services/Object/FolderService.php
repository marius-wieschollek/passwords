<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:03
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCP\IUser;

/**
 * Class FolderService
 *
 * @package OCA\Passwords\Services
 */
class FolderService extends AbstractService {
    const BASE_FOLDER_UUID = '00000000-0000-0000-0000-000000000000';

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var FolderMapper
     */
    protected $folderMapper;

    /**
     * @var HookManager
     */
    protected $hookManager;

    /**
     * FolderService constructor.
     *
     * @param IUser        $user
     * @param HookManager  $hookManager
     * @param FolderMapper $folderMapper
     */
    public function __construct(
        IUser $user,
        HookManager $hookManager,
        FolderMapper $folderMapper
    ) {
        $this->user         = $user;
        $this->folderMapper = $folderMapper;
        $this->hookManager  = $hookManager;
    }

    /**
     * @return Folder[]
     */
    public function getAllFolders(): array {
        /** @var Folder[] $folders */
        return $this->folderMapper->findAll();
    }

    /**
     * @param int $id
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getFolderById(int $id): Folder {
        return $this->folderMapper->findById($id);
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getFolderByUuid(string $uuid): Folder {
        if($uuid === self::BASE_FOLDER_UUID) return $this->getBaseFolder();

        return $this->folderMapper->findByUuid($uuid);
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder[]
     */
    public function getFoldersByParent(string $uuid): array {
        return $this->folderMapper->getByParentFolder($uuid);
    }

    /**
     * @return Folder
     */
    public function getBaseFolder(): Folder {

        $model = $this->createModel(FolderRevisionService::BASE_REVISION_UUID);
        $model->setUuid(self::BASE_FOLDER_UUID);

        return $model;
    }

    /**
     * @param string $revisionUuid
     *
     * @return Folder
     */
    public function createFolder(string $revisionUuid = ''): Folder {
        return $this->createModel($revisionUuid);
    }

    /**
     * @param Folder $folder
     *
     * @return Folder|\OCP\AppFramework\Db\Entity
     */
    public function saveFolder(Folder $folder): Folder {
        if($folder->getUuid() === self::BASE_FOLDER_UUID) return $folder;

        $this->hookManager->emit(Folder::class, 'preSave', [$folder]);
        if(empty($folder->getId())) {
            return $this->folderMapper->insert($folder);
        } else {
            $folder->setUpdated(time());

            return $this->folderMapper->update($folder);
        }
    }

    /**
     * @param Folder $folder
     * @param array  $overwrites
     *
     * @return Folder
     */
    public function cloneFolder(Folder $folder, array $overwrites = []): Folder {
        $this->hookManager->emit(Folder::class, 'preClone', [$folder]);
        /** @var Folder $clone */
        $clone = $this->cloneModel($folder, $overwrites);
        $this->hookManager->emit(Folder::class, 'postClone', [$folder, $clone]);

        return $clone;
    }

    /**
     * @param Folder $folder
     */
    public function deleteFolder(Folder $folder): void {
        $this->hookManager->emit(Folder::class, 'preDelete', [$folder]);
        $folder->setDeleted(true);
        $this->saveFolder($folder);
        $this->hookManager->emit(Folder::class, 'postDelete', [$folder]);
    }

    /**
     * @param Folder $folder
     */
    public function destroyFolder(Folder $folder): void {
        $this->hookManager->emit(Folder::class, 'preDestroy', [$folder]);
        $this->folderMapper->delete($folder);
        $this->hookManager->emit(Folder::class, 'postDestroy', [$folder]);
    }

    /**
     * @param Folder         $folder
     * @param FolderRevision $revision
     *
     * @throws \Exception
     */
    public function setFolderRevision(Folder $folder, FolderRevision $revision): void {
        if($revision->getModel() === $folder->getUuid()) {
            $this->hookManager->emit(Folder::class, 'preSetRevision', [$folder, $revision]);
            $folder->setRevision($revision->getUuid());
            $folder->setUpdated(time());
            $this->saveFolder($folder);
            $this->hookManager->emit(Folder::class, 'postSetRevision', [$folder, $revision]);
        } else {
            throw new \Exception('Folder ID did not match when setting folder revision');
        }
    }

    /**
     * @param string $revisionUuid
     *
     * @return Folder
     */
    protected function createModel(string $revisionUuid): Folder {
        $model = new Folder();
        $model->setUserId($this->user->getUID());
        $model->setUuid($this->generateUuidV4());
        $model->setDeleted(false);
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setRevision($revisionUuid);

        return $model;
    }
}