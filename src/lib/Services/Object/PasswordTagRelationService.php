<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 25.12.17
 * Time: 13:08
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordTagRelation;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCP\IUser;

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
     * AbstractParentEntityService constructor.
     *
     * @param IUser                     $user
     * @param PasswordTagRelationMapper $mapper
     * @param HookManager               $hookManager
     */
    public function __construct(IUser $user, HookManager $hookManager, PasswordTagRelationMapper $mapper) {
        $this->mapper = $mapper;

        parent::__construct($user, $hookManager);
    }

    /**
     * @param string $passwordUuid
     *
     * @return PasswordTagRelation[]
     * @throws \Exception
     */
    public function findByPassword(string $passwordUuid): array {
        return $this->mapper->findAllMatching(['password', $passwordUuid]);
    }

    /**
     * @param string $tagUuid
     *
     * @return PasswordTagRelation[]
     * @throws \Exception
     */
    public function findByTag(string $tagUuid): array {
        return $this->mapper->findAllMatching(['tag', $tagUuid]);
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

        return $this->createModel($password, $tag);
    }

    /**
     * @param AbstractEntity $model
     *
     * @return mixed
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
     * @param PasswordRevision $password
     * @param TagRevision      $tag
     *
     * @return PasswordTagRelation
     */
    protected function createModel(PasswordRevision $password, TagRevision $tag): PasswordTagRelation {
        $model = new PasswordTagRelation();
        $model->setDeleted(false);
        $model->setUserId($this->user->getUID());
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setTag($tag->getModel());
        $model->setTagRevision($tag->getUuid());
        $model->setPassword($password->getModel());
        $model->setPasswordRevision($password->getUuid());
        $model->setHidden($password->isHidden() || $tag->isHidden());

        return $model;
    }
}