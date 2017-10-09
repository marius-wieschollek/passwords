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
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCP\IUser;

/**
 * Class FolderService
 *
 * @package OCA\Passwords\Services
 */
class FolderService {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;
    /**
     * @var FolderMapper
     */
    private $folderMapper;

    /**
     * FolderService constructor.
     *
     * @param IUser             $user
     * @param FolderMapper      $folderMapper
     * @param ValidationService $validationService
     * @param EncryptionService $encryptionService
     */
    public function __construct(
        IUser $user,
        FolderMapper $folderMapper,
        ValidationService $validationService,
        EncryptionService $encryptionService
    ) {
        $this->user              = $user;
        $this->folderMapper      = $folderMapper;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param array $search
     *
     * @return Folder[]
     */
    public function findFolders(array $search = []) {
        return $this->folderMapper->findMatching(
            $search
        );
    }

    /**
     * @param int $folderId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder
     */
    public function getFolderById(int $folderId) {
        return $this->folderMapper->findById(
            $folderId
        );
    }

    /**
     * @param string $folderId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder
     */
    public function getFolderByUuid(string $folderId): Folder {
        return $this->folderMapper->findByUuid(
            $folderId
        );
    }

    /**
     * @param string $name
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return Folder
     */
    public function createFolder(
        string $name,
        string $cseType,
        string $sseType,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): Folder {
        return $this->createFolderModel($name, $cseType, $sseType, $hidden, $trashed, $deleted, $favourite);
    }

    /**
     * @param Folder $folder
     *
     * @return Folder|\OCP\AppFramework\Db\Entity
     */
    public function saveFolder(Folder $folder): Folder {
        if(empty($folder->getId())) {
            return $this->folderMapper->insert($folder);
        } else {
            $folder->setUpdated(time());

            return $this->folderMapper->update($folder);
        }
    }

    /**
     * @param Folder $folder
     */
    public function destroyFolder(Folder $folder) {
        $this->folderMapper->delete($folder);
    }

    /**
     * @param string $name
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return Folder
     */
    protected function createFolderModel(
        string $name,
        string $cseType,
        string $sseType,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): Folder {
        $model = new Folder();
        $model->setUser($this->user->getUID());
        $model->setUuid($this->folderMapper->generateUuidV4());
        $model->setHidden($hidden);
        $model->setTrashed($trashed);
        $model->setDeleted($deleted);
        $model->setFavourite($favourite);
        $model->setName($name);
        $model->setCseType($cseType);
        $model->setSseType($sseType);
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }
}