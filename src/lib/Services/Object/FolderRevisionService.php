<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use Exception;
use OCP\IL10N;
use OCP\AppFramework\Db\Entity;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class FolderRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
class FolderRevisionService extends AbstractRevisionService {

    const BASE_REVISION_UUID = '00000000-0000-0000-0000-000000000000';

    /**
     * @var string
     */
    protected string $class = FolderRevision::class;
    /**
     * @var IL10N
     */
    protected IL10N $l10n;

    /**
     * FolderRevisionService constructor.
     *
     * @param IL10N                $l10n
     * @param UuidHelper           $uuidHelper
     * @param IEventDispatcher     $eventDispatcher
     * @param EnvironmentService   $environment
     * @param FolderRevisionMapper $revisionMapper
     * @param ValidationService    $validationService
     * @param EncryptionService    $encryption
     */
    public function __construct(
        IL10N $l10n,
        UuidHelper $uuidHelper,
        IEventDispatcher $eventDispatcher,
        EnvironmentService $environment,
        FolderRevisionMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryption
    ) {
        parent::__construct($uuidHelper, $eventDispatcher, $environment, $revisionMapper, $validationService, $encryption);
        $this->l10n = $l10n;
    }

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return FolderRevision
     *
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function findByUuid(string $uuid, bool $decrypt = false): RevisionInterface {
        if($uuid === self::BASE_REVISION_UUID) return $this->getBaseRevision();

        return parent::findByUuid($uuid, $decrypt);
    }

    /**
     * @param string $modelUuid
     * @param bool   $decrypt
     *
     * @return FolderRevision
     *
     * @throws Exception
     */
    public function findCurrentRevisionByModel(string $modelUuid, bool $decrypt = false): RevisionInterface {
        if($modelUuid === FolderService::BASE_FOLDER_UUID) return $this->getBaseRevision();

        return parent::findCurrentRevisionByModel($modelUuid, $decrypt);
    }

    /**
     * @return FolderRevision
     */
    public function getBaseRevision(): FolderRevision {
        $model = $this->createModel(
            FolderService::BASE_FOLDER_UUID,
            $this->l10n->t('Home'),
            FolderService::BASE_FOLDER_UUID,
            '',
            EncryptionService::DEFAULT_CSE_ENCRYPTION,
            time(),
            false,
            false,
            false
        );
        $model->setUuid(self::BASE_REVISION_UUID);
        $model->setClient(EnvironmentService::CLIENT_SYSTEM);
        $model->_setDecrypted(true);
        $this->fireEvent('instantiated', $model);

        return $model;
    }

    /**
     * @param string $folder
     * @param string $label
     * @param string $parent
     * @param string $cseKey
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $favorite
     *
     * @return FolderRevision
     * @throws ApiException
     */
    public function create(
        string $folder,
        string $label,
        string $parent,
        string $cseKey,
        string $cseType,
        int $edited,
        bool $hidden,
        bool $trashed,
        bool $favorite
    ): FolderRevision {
        $revision = $this->createModel($folder, $label, $parent, $cseKey, $cseType, $edited, $hidden, $trashed, $favorite);

        $revision = $this->validation->validateFolder($revision);
        $this->fireEvent('instantiated', $revision);

        return $revision;
    }

    /**
     * @param RevisionInterface|EntityInterface $model
     *
     * @return FolderRevision|Entity
     * @throws Exception
     */
    public function save(EntityInterface $model): EntityInterface {
        if(
            $model->getUuid() === self::BASE_REVISION_UUID ||
            $model->getModel() === FolderService::BASE_FOLDER_UUID) {
            return $model;
        }

        return parent::save($model);
    }

    /**
     * @param RevisionInterface|EntityInterface $entity
     * @param array                             $overwrites
     *
     * @return EntityInterface
     * @throws Exception
     */
    public function clone(EntityInterface $entity, array $overwrites = []): EntityInterface {
        if($entity->getUuid() === self::BASE_REVISION_UUID) return $entity;

        return parent::clone($entity, $overwrites);
    }

    /**
     * @param RevisionInterface|EntityInterface $entity
     *
     * @throws Exception
     */
    public function delete(EntityInterface $entity): void {
        if($entity->getUuid() === self::BASE_REVISION_UUID) return;

        parent::delete($entity);
    }

    /**
     * @param string $model
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param string $cseKey
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $favorite
     *
     * @return FolderRevision
     */
    protected function createModel(
        string $model,
        string $label,
        string $parent,
        string $cseKey,
        string $cseType,
        int $edited,
        bool $hidden,
        bool $trashed,
        bool $favorite
    ): FolderRevision {
        $revision = new FolderRevision();
        if($this->userId !== null) $revision->setUserId($this->userId);
        $revision->setUuid($this->uuidHelper->generateUuid());
        $revision->setDeleted(false);
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setFavorite($favorite);
        $revision->setLabel($label);
        $revision->setParent($parent);
        $revision->setCseKey($cseKey);
        $revision->setCseType($cseType);
        $revision->setHidden($hidden);
        $revision->setTrashed($trashed);
        $revision->setEdited($edited);
        $revision->setSseType($this->getSseEncryption($cseType));
        $revision->setClient($this->environment->getClient());

        return $revision;
    }
}