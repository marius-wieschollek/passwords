<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 13:07
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCP\IUser;

/**
 * Class AbstractModelService
 *
 * @package OCA\AbstractParentEntitys\Services\Object
 */
abstract class AbstractModelService extends AbstractService {

    /**
     * @var AbstractMapper
     */
    protected $mapper;

    /**
     * AbstractParentEntityService constructor.
     *
     * @param IUser          $user
     * @param AbstractMapper $mapper
     * @param HookManager    $hookManager
     */
    public function __construct(IUser $user, HookManager $hookManager, AbstractMapper $mapper) {
        $this->mapper = $mapper;

        parent::__construct($user, $hookManager);
    }

    /**
     * @return AbstractModelEntity[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|AbstractModelEntity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): AbstractModelEntity {
        return $this->mapper->findByUuid($uuid);
    }

    /**
     * @return AbstractModelEntity
     */
    public function create(): AbstractModelEntity {
        return $this->createModel();
    }

    /**
     * @param AbstractEntity|AbstractModelEntity $model
     *
     * @return AbstractModelEntity|\OCP\AppFramework\Db\Entity
     */
    public function save(AbstractEntity $model): AbstractEntity {
        $this->hookManager->emit($this->class, 'preSave', [$model]);
        if(empty($model->getId())) {
            return $this->mapper->insert($model);
        } else {
            $model->setUpdated(time());

            return $this->mapper->update($model);
        }
    }

    /**
     * @param AbstractModelEntity    $model
     * @param AbstractRevisionEntity $revision
     *
     * @throws \Exception
     */
    public function setRevision(AbstractModelEntity $model, AbstractRevisionEntity $revision): void {
        if($revision->getModel() === $model->getUuid()) {
            $this->hookManager->emit($this->class, 'preSetRevision', [$model, $revision]);
            $model->setRevision($revision->getUuid());
            $this->save($model);
            $this->hookManager->emit($this->class, 'postSetRevision', [$model, $revision]);
        } else {
            throw new \Exception('Revision did not belong to model when setting model revision');
        }
    }

    /**
     * @return AbstractModelEntity
     */
    protected function createModel(): AbstractModelEntity {
        /** @var AbstractModelEntity $model */
        $model = new $this->class();
        $model->setDeleted(false);
        $model->setUserId($this->user->getUID());
        $model->setUuid($this->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }

    /**
     * @param AbstractEntity|AbstractModelEntity $original
     * @param array                              $overwrites
     *
     * @return AbstractEntity|AbstractModelEntity
     */
    protected function cloneModel(AbstractEntity $original, array $overwrites = []): AbstractEntity {

        /** @var AbstractModelEntity $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->setUuid($this->generateUuidV4());

        return $clone;
    }

}