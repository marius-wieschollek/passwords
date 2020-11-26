<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use Exception;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class ShareService
 *
 * @package OCA\Passwords\Services\Object
 */
class ShareService extends AbstractService {

    /**
     * @var ShareMapper|AbstractMapper
     */
    protected AbstractMapper $mapper;

    /**
     * @var string
     */
    protected string $class = Share::class;

    /**
     * ShareService constructor.
     *
     * @param UuidHelper         $uuidHelper
     * @param IEventDispatcher   $eventDispatcher
     * @param ShareMapper        $mapper
     * @param EnvironmentService $environment
     */
    public function __construct(UuidHelper $uuidHelper, IEventDispatcher $eventDispatcher, ShareMapper $mapper, EnvironmentService $environment) {
        $this->mapper = $mapper;

        parent::__construct($uuidHelper, $eventDispatcher, $environment);
    }

    /**
     * @return ModelInterface[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @param string $userId
     *
     * @return Share[]
     * @throws Exception
     */
    public function findWithUserId(string $userId): array {
        return $this->mapper->findAllByUserIdOrReceiverId($userId);
    }

    /**
     * @param string $passwordUuid
     *
     * @return Share[]
     *
     * @throws Exception
     */
    public function findBySourcePassword(string $passwordUuid): array {
        return $this->mapper->findAllByField('source_password', $passwordUuid);
    }

    /**
     * @param string $passwordUuid
     *
     * @return Share|EntityInterface|null
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByTargetPassword(string $passwordUuid): Share {
        return $this->mapper->findOneByField('target_password', $passwordUuid);
    }

    /**
     * @return Share[]
     *
     * @throws Exception
     */
    public function findBySourceUpdated(): array {
        return $this->mapper->findAllByFields(
            ['source_updated', true, IQueryBuilder::PARAM_BOOL],
            ['target_updated', null, IQueryBuilder::PARAM_NULL, 'neq']
        );
    }

    /**
     * @return Share[]
     *
     * @throws Exception
     */
    public function findByTargetUpdated(): array {
        return $this->mapper->findAllByField('target_updated', true, IQueryBuilder::PARAM_BOOL);
    }

    /**
     * @param string $passwordUuid
     * @param string $userId
     *
     * @return Share|EntityInterface|null
     *
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findBySourcePasswordAndReceiver(string $passwordUuid, string $userId): ?Share {
        return $this->mapper->findOneByFields(
            ['source_password', $passwordUuid],
            ['receiver', $userId]
        );
    }

    /**
     * @return Share[]
     * @throws Exception
     */
    public function findNew(): array {
        return $this->mapper->findAllByField('target_password', null, IQueryBuilder::PARAM_NULL);
    }

    /**
     * @return Share[]
     * @throws Exception
     */
    public function findExpired(): array {
        return $this->mapper->findAllByField('expires', time(), IQueryBuilder::PARAM_INT, 'lte');
    }

    /**
     * @param string $uuid
     *
     * @return Share|EntityInterface
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): Share {
        return $this->mapper->findByUuid($uuid);
    }

    /**
     * @param string   $passwordId
     * @param string   $receiverId
     * @param string   $type
     * @param bool     $editable
     * @param int|null $expires
     * @param bool     $shareable
     *
     * @return Share|ModelInterface
     */
    public function create(
        string $passwordId,
        string $receiverId,
        string $type,
        bool $editable,
        int $expires = null,
        bool $shareable = true
    ): Share {
        $model = $this->createModel($passwordId, $receiverId, $type, $editable, $expires, $shareable);
        $this->fireEvent('instantiated', $model);

        return $model;
    }

    /**
     * @param EntityInterface|Share $model
     *
     * @return EntityInterface|Share|Entity
     */
    public function save(EntityInterface $model): EntityInterface {
        if(empty($model->getId())) {
            $this->fireEvent('beforeCreated', $model);
            $saved = $this->mapper->insert($model);
            $this->fireEvent('created', $model);
            $this->fireEvent('afterCreated', $model);
        } else {
            $this->fireEvent('beforeUpdated', $model);
            $model->setUpdated(time());
            $saved = $this->mapper->update($model);
            $this->fireEvent('updated', $model);
            $this->fireEvent('afterUpdated', $model);
        }

        return $saved;
    }

    /**
     * @param string   $passwordId
     * @param string   $receiverId
     * @param string   $type
     * @param bool     $editable
     * @param int|null $expires
     * @param bool     $shareable
     *
     * @return Share
     */
    protected function createModel(
        string $passwordId,
        string $receiverId,
        string $type,
        bool $editable,
        ?int $expires,
        bool $shareable
    ): Share {

        $model = new Share();
        $model->setDeleted(false);
        $model->setUserId($this->userId);
        $model->setUuid($this->uuidHelper->generateUuid());
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setSourcePassword($passwordId);
        $model->setSourceUpdated(true);
        $model->setReceiver($receiverId);
        $model->setShareable($shareable);
        $model->setEditable($editable);
        $model->setExpires($expires);
        $model->setType($type);
        $model->setClient($this->environment->getClient());

        return $model;
    }

    /**
     * @param Share|EntityInterface $original
     * @param array                 $overwrites
     *
     * @return Share
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {

        /** @var Share $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->setUuid($this->uuidHelper->generateUuid());
        $clone->setClient($this->environment->getClient());

        return $clone;
    }
}