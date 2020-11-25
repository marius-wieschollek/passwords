<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Registration;
use OCA\Passwords\Db\RegistrationMapper;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class RegistrationService
 *
 * @package OCA\Passwords\Services\Object
 */
class RegistrationService extends AbstractService {

    /**
     * @var string
     */
    protected string $class = Registration::class;

    /**
     * @var RegistrationMapper|AbstractMapper
     */
    protected AbstractMapper $mapper;

    /**
     * RegistrationService constructor.
     *
     * @param RegistrationMapper $mapper
     * @param UuidHelper         $uuidHelper
     * @param IEventDispatcher   $eventDispatcher
     * @param HookManager        $hookManager
     * @param EnvironmentService $environment
     */
    public function __construct(RegistrationMapper $mapper, UuidHelper $uuidHelper, IEventDispatcher $eventDispatcher, HookManager $hookManager, EnvironmentService $environment) {
        $this->mapper = $mapper;

        parent::__construct($uuidHelper, $eventDispatcher, $hookManager, $environment);
    }

    /**
     * @return Registration
     */
    public function create(): Registration {
        $model = $this->createModel();
        $this->fireEvent('instantiated', $model);
        $this->hookManager->emit(Registration::class, 'postCreate', [$model]);

        return $model;
    }

    /**
     * @param Registration|EntityInterface $model
     *
     * @return Registration|EntityInterface
     */
    public function save(EntityInterface $model): EntityInterface {
        $this->hookManager->emit(Registration::class, 'preSave', [$model]);
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
        $this->hookManager->emit(Registration::class, 'postSave', [$saved]);

        return $saved;
    }

    /**
     *
     */
    public function clearCache(): void {
        $this->mapper->clearEntityCache();
    }

    /**
     * @return Registration
     */
    protected function createModel(): Registration {
        $model = new Registration();
        $model->setDeleted(false);
        $model->setUserId($this->userId);
        $model->setUuid($this->uuidHelper->generateUuid());
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }

    /**
     * @param Registration|EntityInterface $original
     * @param array                        $overwrites
     *
     * @return Registration
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {

        /** @var Registration $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->setUuid($this->uuidHelper->generateUuid());

        return $clone;
    }
}