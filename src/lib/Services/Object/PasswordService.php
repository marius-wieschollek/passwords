<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:18
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCP\IUser;

/**
 * Class PasswordService
 *
 * @package OCA\Passwords\Services
 */
class PasswordService extends AbstractService {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var PasswordMapper
     */
    protected $passwordMapper;

    /**
     * @var HookManager
     */
    protected $hookManager;

    /**
     * PasswordService constructor.
     *
     * @param IUser          $user
     * @param PasswordMapper $passwordMapper
     * @param HookManager    $hookManager
     */
    public function __construct(IUser $user, PasswordMapper $passwordMapper, HookManager $hookManager) {
        $this->user           = $user;
        $this->passwordMapper = $passwordMapper;
        $this->hookManager    = $hookManager;
    }

    /**
     * @return Password[]
     */
    public function getAllPasswords() {
        return $this->passwordMapper->findAll();
    }

    /**
     * @param int $id
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Password
     */
    public function getPasswordById(int $id) {
        return $this->passwordMapper->findById($id);
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Password
     */
    public function getPasswordByUuid(string $uuid): Password {
        return $this->passwordMapper->findByUuid($uuid);
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Password[]
     */
    public function getPasswordsByFolder(string $uuid): array {
        return $this->passwordMapper->getByFolder($uuid);
    }

    /**
     * @param string $revisionUuid
     *
     * @return Password
     */
    public function createPassword(string $revisionUuid = ''): Password {
        return $this->createModel($revisionUuid);
    }

    /**
     * @param Password $password
     *
     * @return Password|\OCP\AppFramework\Db\Entity
     */
    public function savePassword(Password $password): Password {
        $this->hookManager->emit(Password::class, 'preSave', [$password]);
        if(empty($password->getId())) {
            return $this->passwordMapper->insert($password);
        } else {
            $password->setUpdated(time());

            return $this->passwordMapper->update($password);
        }
    }

    /**
     * @param Password $password
     * @param array    $overwrites
     *
     * @return Password
     */
    public function clonePassword(Password $password, array $overwrites = []): Password {
        $this->hookManager->emit(Password::class, 'preClone', [$password]);
        /** @var Password $clone */
        $clone = $this->cloneModel($password, $overwrites);
        $clone->setUuid($this->passwordMapper->generateUuidV4());
        $this->hookManager->emit(Password::class, 'postClone', [$password, $clone]);

        return $clone;
    }

    /**
     * @param Password $password
     */
    public function deletePassword(Password $password) {
        $this->hookManager->emit(Password::class, 'preDelete', [$password]);
        $password->setDeleted(true);
        $this->savePassword($password);
        $this->hookManager->emit(Password::class, 'postDelete', [$password]);
    }

    /**
     * @param Password $password
     */
    public function destroyPassword(Password $password) {
        if(!$password->isDeleted()) $this->deletePassword($password);
        $this->hookManager->emit(Password::class, 'preDestroy', [$password]);
        $this->passwordMapper->delete($password);
        $this->hookManager->emit(Password::class, 'postDestroy', [$password]);
    }

    /**
     * @param Password         $password
     * @param PasswordRevision $revision
     *
     * @throws \Exception
     */
    public function setPasswordRevision(Password $password, PasswordRevision $revision) {
        if($revision->getModel() === $password->getUuid()) {
            $this->hookManager->emit(Password::class, 'preSetRevision', [$password, $revision]);
            $password->setRevision($revision->getUuid());
            $password->setUpdated(time());
            $this->savePassword($password);
            $this->hookManager->emit(Password::class, 'postSetRevision', [$password, $revision]);
        } else {
            throw new \Exception('Password ID did not match when setting password revision');
        }
    }

    /**
     * @param string $revision
     *
     * @return Password
     */
    protected function createModel(string $revision): Password {
        $model = new Password();
        $model->setDeleted(false);
        $model->setUserId($this->user->getUID());
        $model->setUuid($this->passwordMapper->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());

        $model->setRevision($revision);

        return $model;
    }

}