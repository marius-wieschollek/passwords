<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:18
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordFolderRelation;
use OCA\Passwords\Db\PasswordFolderRelationMapper;
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
     * @var PasswordFolderRelationMapper
     */
    protected $folderRelationMapper;

    /**
     * PasswordService constructor.
     *
     * @param IUser                        $user
     * @param PasswordMapper               $passwordMapper
     * @param PasswordFolderRelationMapper $folderRelationMapper
     */
    public function __construct(
        IUser $user,
        PasswordMapper $passwordMapper,
        PasswordFolderRelationMapper $folderRelationMapper
    ) {
        $this->user           = $user;
        $this->passwordMapper = $passwordMapper;
        $this->folderRelationMapper = $folderRelationMapper;
    }

    /**
     * @param string $passwordUuid
     *
     * @return array|\OCA\Passwords\Db\AbstractEntity[]|\OCP\AppFramework\Db\Entity[]
     */
    public function getPasswordFolderRelations(string $passwordUuid) {
        return $this->folderRelationMapper->findByPassword($passwordUuid);
    }

    /**
     * @param string $passwordUuid
     * @param array  $folders
     */
    public function setPasswordFolderRelations(string $passwordUuid, array $folders) {
        $relations = $this->folderRelationMapper->findByPassword($passwordUuid);

        foreach($relations as $relation) {
            if(($key = array_search($relation->getFolder(), $folders)) !== false) {
                unset($folders[$key]);
                $relation->setUpdated(time());
                $this->folderRelationMapper->update($relation);
            } else {
                $relation->setUpdated(time());
                $relation->setDeleted(true);
                $this->folderRelationMapper->update($relation);
            }
        }

        foreach($folders as $folder) {
            $relation = new PasswordFolderRelation();
            $relation->setPassword($passwordUuid);
            $relation->setFolder($folder);
            $relation->setUser($this->user->getUID());
            $relation->setUpdated(time());
            $relation->setCreated(time());
            $this->folderRelationMapper->insert($relation);
        }
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
        if($revision->getPasswordId() === $password->getUuid()) {
            $password->setRevision($revision->getUuid());
            $password->setUpdated(time());
            $this->savePassword($password);
        } else {
            throw new \Exception('Password ID did not match when setting password revision');
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