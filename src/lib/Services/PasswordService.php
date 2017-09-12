<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:18
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\Revision;
use OCP\IUser;

/**
 * Class PasswordService
 *
 * @package OCA\Passwords\Services
 */
class PasswordService {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var PasswordMapper
     */
    protected $passwordMapper;

    /**
     * PasswordService constructor.
     *
     * @param IUser          $user
     * @param PasswordMapper $passwordMapper
     */
    public function __construct(
        IUser $user,
        PasswordMapper $passwordMapper
    ) {
        $this->user           = $user;
        $this->passwordMapper = $passwordMapper;
    }

    /**
     * @param array $search
     *
     * @return Password[]
     */
    public function findPasswords(array $search = []) {
        return $this->passwordMapper->findMatching(
            $search
        );
    }

    /**
     * @param int $passwordId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Password
     */
    public function getPasswordById(int $passwordId) {
        return $this->passwordMapper->findById(
            $passwordId
        );
    }

    /**
     * @param string $passwordId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Password
     */
    public function getPasswordByUuid(string $passwordId): Password {
        return $this->passwordMapper->findByUuid(
            $passwordId
        );
    }

    /**
     * @param string $revisionUuid
     *
     * @return Password
     */
    public function createPassword(string $revisionUuid = ''): Password {
        return $this->createPasswordModel($revisionUuid);
    }

    /**
     * @param Password $password
     *
     * @return Password|\OCP\AppFramework\Db\Entity
     */
    public function savePassword(Password $password): Password {
        if(empty($password->getId())) {
            return $this->passwordMapper->insert($password);
        } else {
            $password->setUpdated(time());

            return $this->passwordMapper->update($password);
        }
    }

    /**
     * @param Password $password
     */
    public function destroyPassword(Password $password) {
        $this->passwordMapper->delete($password);
    }

    /**
     * @param Password $password
     * @param Revision $revision
     *
     * @throws \Exception
     */
    public function setPasswordRevision(Password $password, Revision $revision) {
        if($revision->getPasswordId() === $password->getId()) {
            $password->setRevision($revision->getUuid());
            $password->setUpdated(time());
            $this->savePassword($password);
        } else {
            throw new \Exception('Password ID does not match when setting password revision');
        }
    }

    /**
     * @param string $revision
     *
     * @return Password
     */
    protected function createPasswordModel(string $revision): Password {
        $model = new Password();
        $model->setDeleted(0);
        $model->setUser($this->user->getUID());
        $model->setUuid($this->passwordMapper->generateUuidV4());
        $model->setRevision($revision);
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }

}