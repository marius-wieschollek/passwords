<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Session;
use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;

/**
 * Class SessionService
 *
 * @package OCA\Passwords\Services\Object
 */
class SessionService extends AbstractService {

    /**
     * @var string
     */
    protected $class = Session::class;

    /**
     * SessionService constructor.
     *
     * @param HookManager        $hookManager
     * @param SessionMapper      $mapper
     * @param EnvironmentService $environment
     */
    public function __construct(HookManager $hookManager, SessionMapper $mapper, EnvironmentService $environment) {
        $this->mapper = $mapper;

        parent::__construct($hookManager, $environment);
    }

    /**
     * @return Session[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @param string $uuid
     *
     * @return EntityInterface|Session|\OCP\AppFramework\Db\Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid) {
        return $this->mapper->findByUuid($uuid);
    }

    /**
     * @return Session
     */
    public function create(): Session {
        $model = $this->createModel();
        $this->hookManager->emit($this->class, 'postCreate', [$model]);

        return $model;
    }

    /**
     * @param EntityInterface|\OCP\AppFramework\Db\Entity $model
     *
     * @return mixed
     */
    public function save(EntityInterface $model): EntityInterface {
        $this->hookManager->emit($this->class, 'preSave', [$model]);
        if(empty($model->getId())) {
            $saved = $this->mapper->insert($model);
        } else {
            $model->setUpdated(time());

            $saved = $this->mapper->update($model);
        }
        $this->hookManager->emit($this->class, 'postSave', [$saved]);

        return $saved;
    }

    /**
     * @return Session
     */
    protected function createModel(): Session {
        /** @var Session $model */
        $model = new $this->class();
        $model->setUserId($this->userId);
        $model->setUuid($this->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());
        $model->setDeleted(false);

        return $model;
    }
}