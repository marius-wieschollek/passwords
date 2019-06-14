<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordTagRelation;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\Entity;

/**
 * Class PasswordTagRelationService
 *
 * @package OCA\Passwords\Services\Object
 */
class PasswordTagRelationService extends AbstractService {

    /**
     * @var PasswordTagRelationMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = PasswordTagRelation::class;

    /**
     * PasswordTagRelationService constructor.
     *
     * @param UuidHelper                $uuidHelper
     * @param HookManager               $hookManager
     * @param EnvironmentService        $environment
     * @param PasswordTagRelationMapper $mapper
     */
    public function __construct(UuidHelper $uuidHelper, HookManager $hookManager, EnvironmentService $environment, PasswordTagRelationMapper $mapper) {
        $this->mapper = $mapper;

        parent::__construct($uuidHelper, $hookManager, $environment);
    }

    /**
     * @return PasswordTagRelation[]
     */
    public function findAll(): array {
        return $this->mapper->findAll();
    }

    /**
     * @param string $passwordUuid
     *
     * @return PasswordTagRelation[]
     * @throws \Exception
     */
    public function findByPassword(string $passwordUuid): array {
        return $this->mapper->findAllByField('password', $passwordUuid);
    }

    /**
     * @param string $tagUuid
     *
     * @return PasswordTagRelation[]
     * @throws \Exception
     */
    public function findByTag(string $tagUuid): array {
        return $this->mapper->findAllByField('tag', $tagUuid);
    }

    /**
     * @param string $tagUuid
     * @param string $passwordUuid
     *
     * @return PasswordTagRelation|EntityInterface|null
     * @throws \Exception
     */
    public function findByTagAndPassword(string $tagUuid, string $passwordUuid): ?PasswordTagRelation {
        return $this->mapper->findOneByFields(
            ['password', $passwordUuid],
            ['tag', $tagUuid]
        );
    }

    /**
     * @param PasswordRevision $password
     * @param TagRevision      $tag
     *
     * @return PasswordTagRelation
     * @throws \Exception
     */
    public function create(PasswordRevision $password, TagRevision $tag): PasswordTagRelation {
        if($password->getUserId() != $tag->getUserId()) {
            throw new \Exception('User ID did not match when creating password to tag relation');
        }

        $model = $this->createModel($password, $tag);
        $this->hookManager->emit($this->class, 'postCreate', [$model]);

        return $model;
    }

    /**
     * @param EntityInterface|Entity $model
     *
     * @return mixed
     */
    public function save(EntityInterface $model): EntityInterface {
        $this->hookManager->emit($this->class, 'preSave', [$model]);
        if(empty($model->getId())) {
            return $this->mapper->insert($model);
        } else {
            $model->setUpdated(time());

            return $this->mapper->update($model);
        }
    }

    /**
     * @param EntityInterface|PasswordTagRelation $original
     * @param array           $overwrites
     *
     * @return EntityInterface|PasswordTagRelation
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {
        /** @var PasswordTagRelation $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->setClient($this->environment->getClient());

        return $clone;
    }

    /**
     * @param PasswordRevision $password
     * @param TagRevision      $tag
     *
     * @return PasswordTagRelation
     */
    protected function createModel(PasswordRevision $password, TagRevision $tag): PasswordTagRelation {
        $model = new PasswordTagRelation();
        $model->setUuid($this->uuidHelper->generateUuid());
        $model->setDeleted(false);
        $model->setUserId($this->userId);
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setTag($tag->getModel());
        $model->setTagRevision($tag->getUuid());
        $model->setPassword($password->getModel());
        $model->setPasswordRevision($password->getUuid());
        $model->setHidden($password->isHidden() || $tag->isHidden());
        $model->setClient($this->environment->getClient());

        return $model;
    }
}