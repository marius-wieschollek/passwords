<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 14:29
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Hooks\Manager\HookManager;

/**
 * Class ShareService
 *
 * @package      OCA\Passwords\Services\Object
 * @noinspection PhpSignatureMismatchDuringInheritanceInspection
 */
class ShareService extends AbstractService {

    /**
     * @var ShareMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Share::class;

    /**
     * AbstractParentEntityService constructor.
     *
     * @param string      $userId
     * @param HookManager $hookManager
     * @param ShareMapper $mapper
     */
    public function __construct(?string $userId, HookManager $hookManager, ShareMapper $mapper) {
        $this->mapper = $mapper;

        parent::__construct($userId, $hookManager);
    }

    /**
     * @return ModelInterface[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @param string $passwordUuid
     *
     * @return Share[]
     *
     * @throws \Exception
     */
    public function findBySourcePassword(string $passwordUuid): array {
        return $this->mapper->findAllMatching(['source_password', $passwordUuid]);
    }

    /**
     * @param string $passwordUuid
     *
     * @return Share|EntityInterface|null
     *
     * @throws \Exception
     */
    public function findByTargetPassword(string $passwordUuid): ?Share {
        return $this->mapper->findOneMatching(['target_password', $passwordUuid]);
    }

    /**
     * @return Share[]
     *
     * @throws \Exception
     */
    public function findBySourceUpdated(): array {
        return $this->mapper->findAllMatching(['source_updated', true]);
    }

    /**
     * @return Share[]
     *
     * @throws \Exception
     */
    public function findByTargetUpdated(): array {
        return $this->mapper->findAllMatching(['target_updated', true]);
    }

    /**
     * @param string $passwordUuid
     *
     * @return Share|EntityInterface|null
     *
     * @throws \Exception
     */
    public function findBySourcePasswordAndReceiver(string $passwordUuid, string $userId): ?Share {
        return $this->mapper->findOneMatching(
            [
                ['source_password', $passwordUuid],
                ['receiver', $userId]
            ]
        );
    }

    /**
     * @return Share[]
     * @throws \Exception
     */
    public function findNew(): array {
        return $this->mapper->findAllMatching(['target_password', null]);
    }

    /**
     * @return Share[]
     * @throws \Exception
     */
    public function findExpired(): array {
        return $this->mapper->findAllMatching(['expires', time(), '']);
    }

    /**
     * @param string $uuid
     *
     * @return Share|EntityInterface
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
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
        $this->hookManager->emit(Share::class, 'postCreate', [$model]);

        return $model;
    }

    /**
     * @param EntityInterface|Share $model
     *
     * @return EntityInterface|Share|\OCP\AppFramework\Db\Entity
     */
    public function save(EntityInterface $model): EntityInterface {
        $this->hookManager->emit(Share::class, 'preSave', [$model]);
        if(empty($model->getId())) {
            $saved = $this->mapper->insert($model);
        } else {
            $model->setUpdated(time());

            $saved = $this->mapper->update($model);
        }
        $this->hookManager->emit(Share::class, 'postSave', [$saved]);

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

        /** @var Share $model */
        $model = new Share();
        $model->setDeleted(false);
        $model->setUserId($this->userId);
        $model->setUuid($this->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setSourcePassword($passwordId);
        $model->setSourceUpdated(true);
        $model->setReceiver($receiverId);
        $model->setShareable($shareable);
        $model->setEditable($editable);
        $model->setExpires($expires);
        $model->setType($type);

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
        $clone->setUuid($this->generateUuidV4());

        return $clone;
    }
}