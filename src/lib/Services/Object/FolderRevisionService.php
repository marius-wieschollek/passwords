<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 17:26
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCP\IUser;

/**
 * Class FolderRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
class FolderRevisionService extends AbstractService {

    const BASE_REVISION_UUID = '00000000-0000-0000-0000-000000000000';

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
     * @var FolderRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var HookManager
     */
    protected $hookManager;

    /**
     * FolderService constructor.
     *
     * @param IUser                $user
     * @param HookManager          $hookManager
     * @param FolderRevisionMapper $revisionMapper
     * @param ValidationService    $validationService
     * @param EncryptionService    $encryptionService
     */
    public function __construct(
        IUser $user,
        HookManager $hookManager,
        FolderRevisionMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryptionService
    ) {
        $this->user              = $user;
        $this->hookManager       = $hookManager;
        $this->revisionMapper    = $revisionMapper;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param int  $id
     * @param bool $decrypt
     *
     * @return FolderRevision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getRevisionById(int $id, bool $decrypt = true): FolderRevision {
        /** @var FolderRevision $revision */
        $revision = $this->revisionMapper->findById($id);
        if(!$decrypt) return $revision;

        return $this->encryptionService->decryptFolder($revision);
    }

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return FolderRevision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getRevisionByUuid(string $uuid, bool $decrypt = true): FolderRevision {
        if($uuid === self::BASE_REVISION_UUID) return $this->getBaseRevision();

        /** @var FolderRevision $revision */
        $revision = $this->revisionMapper->findByUuid($uuid);
        if(!$decrypt) return $revision;

        return $this->encryptionService->decryptFolder($revision);
    }

    /**
     * @param Folder $folder
     * @param bool   $decrypt
     *
     * @return FolderRevision[]
     * @throws \Exception
     */
    public function getRevisionsByFolder(Folder $folder, bool $decrypt = true): array {
        /** @var FolderRevision[] $revisions */
        $revisions = $this->revisionMapper->findAllMatching(['folder', $folder->getUuid()]);
        if(!$decrypt) return $revisions;

        foreach ($revisions as $revision) {
            $this->encryptionService->decryptFolder($revision);
        }

        return $revisions;
    }

    /**
     * @param Folder $folder
     * @param bool   $decrypt
     *
     * @return FolderRevision
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getCurrentRevision(Folder $folder, bool $decrypt = true): FolderRevision {
        return $this->getRevisionByUuid($folder->getRevision(), $decrypt);
    }

    /**
     * @return FolderRevision
     */
    public function getBaseRevision(): FolderRevision {

        $model = $this->createModel(
            FolderService::BASE_FOLDER_UUID,
            'Home',
            FolderService::BASE_FOLDER_UUID,
            EncryptionService::DEFAULT_CSE_ENCRYPTION,
            EncryptionService::DEFAULT_SSE_ENCRYPTION,
            false,
            false,
            false,
            false
        );
        $model->setUuid(self::BASE_REVISION_UUID);
        $model->_setDecrypted(true);

        return $model;
    }

    /**
     * @param string $folder
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return FolderRevision
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function createRevision(
        string $folder,
        string $label,
        string $parent,
        string $cseType,
        string $sseType,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): FolderRevision {
        $model = $this->createModel($folder, $label, $parent, $cseType, $sseType, $hidden, $trashed, $deleted, $favourite);

        $model = $this->validationService->validateFolder($model);

        return $model;
    }

    /**
     * @param FolderRevision $revision
     *
     * @return FolderRevision|\OCP\AppFramework\Db\Entity
     * @throws \Exception
     */
    public function saveRevision(FolderRevision $revision): FolderRevision {
        $this->hookManager->emit(FolderRevision::class, 'preSave', [$revision]);

        if($revision->_isDecrypted()) $revision = $this->encryptionService->encryptFolder($revision);
        if(empty($revision->getId())) {
            return $this->revisionMapper->insert($revision);
        } else {
            $revision->setUpdated(time());

            return $this->revisionMapper->update($revision);
        }
    }

    /**
     * @param FolderRevision $revision
     * @param array          $overwrites
     *
     * @return FolderRevision
     */
    public function cloneRevision(FolderRevision $revision, array $overwrites = []): FolderRevision {
        $this->hookManager->emit(FolderRevision::class, 'preClone', [$revision]);
        /** @var FolderRevision $clone */
        $clone = $this->cloneModel($revision, $overwrites);
        $this->hookManager->emit(FolderRevision::class, 'postClone', [$revision, $clone]);

        return $clone;
    }

    /**
     * @param FolderRevision $revision
     *
     * @throws \Exception
     */
    public function deleteRevision(FolderRevision $revision): void {
        $this->hookManager->emit(FolderRevision::class, 'preDelete', [$revision]);
        $revision->setDeleted(true);
        $this->saveRevision($revision);
        $this->hookManager->emit(FolderRevision::class, 'postDelete', [$revision]);
    }

    /**
     * @param string $folder
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return FolderRevision
     */
    protected function createModel(
        string $folder,
        string $label,
        string $parent,
        string $cseType,
        string $sseType,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): FolderRevision {
        $model = new FolderRevision();
        $model->setUserId($this->user->getUID());
        $model->setUuid($this->generateUuidV4());
        $model->setHidden($hidden);
        $model->setTrashed($trashed);
        $model->setDeleted($deleted);
        $model->setModel($folder);
        $model->setFavourite($favourite);
        $model->setLabel($label);
        $model->setParent($parent);
        $model->setCseType($cseType);
        $model->setSseType($sseType);
        $model->setCreated(time());
        $model->setUpdated(time());
        $model->_setDecrypted(true);

        return $model;
    }
}