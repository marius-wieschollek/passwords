<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 17:26
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Services\EncryptionService;

/**
 * Class FolderRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
class FolderRevisionService extends AbstractRevisionService {

    const BASE_REVISION_UUID = '00000000-0000-0000-0000-000000000000';

    protected $class = FolderRevision::class;

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return FolderRevision
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function findByUuid(string $uuid, bool $decrypt = true): AbstractRevisionEntity {
        if($uuid === self::BASE_REVISION_UUID) return $this->getBaseRevision();

        return parent::findByUuid($uuid, $decrypt);
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
    public function create(
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