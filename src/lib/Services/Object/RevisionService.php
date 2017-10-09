<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 23:34
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Revision;
use OCA\Passwords\Db\RevisionMapper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCP\IUser;

/**
 * Class RevisionService
 *
 * @package OCA\Passwords\Services
 */
class RevisionService {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;

    /**
     * @var RevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var AbstractSecurityCheckHelper
     */
    protected $securityCheck;

    /**
     * PasswordService constructor.
     *
     * @param IUser                       $user
     * @param ValidationService           $validationService
     * @param EncryptionService           $encryptionService
     * @param RevisionMapper              $revisionMapper
     * @param AbstractSecurityCheckHelper $securityCheck
     */
    public function __construct(
        IUser $user,
        ValidationService $validationService,
        EncryptionService $encryptionService,
        RevisionMapper $revisionMapper,
        AbstractSecurityCheckHelper $securityCheck
    ) {
        $this->user              = $user;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;
        $this->revisionMapper    = $revisionMapper;
        $this->securityCheck     = $securityCheck;
    }

    /**
     * @param int $revisionId
     *
     * @return Revision
     */
    public function getRevisionById(int $revisionId): Revision {
        /** @var Revision $revision */
        $revision = $this->revisionMapper->findById(
            $revisionId
        );

        return $this->encryptionService->decryptRevision($revision);
    }

    /**
     * @param string $revisionId
     *
     * @return Revision
     */
    public function getRevisionByUuid(string $revisionId): Revision {
        /** @var Revision $revision */
        $revision = $this->revisionMapper->findByUuid(
            $revisionId
        );

        return $this->encryptionService->decryptRevision($revision);
    }

    /**
     * @param string $passwordId
     *
     * @return Revision[]
     */
    public function getRevisionsByPassword(string $passwordId): array {
        /** @var Revision[] $revisions */
        $revisions = $this->revisionMapper->findAllMatching(
            ['password_id', $passwordId]
        );

        foreach($revisions as $revision) {
            $this->encryptionService->decryptRevision($revision);
        }

        return $revisions;
    }

    /**
     * @param Password $password
     *
     * @return Revision
     */
    public function getCurrentRevision(Password $password): Revision {
        /** @var Revision $revision */
        return $this->getRevisionByUuid(
            $password->getRevision()
        );
    }

    /**
     * @param string $passwordId
     * @param string $login
     * @param string $password
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $title
     * @param string $url
     * @param string $notes
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return Revision
     */
    public function createRevision(
        string $passwordId,
        string $password,
        string $login,
        string $cseType,
        string $sseType,
        string $hash,
        string $title,
        string $url,
        string $notes,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): Revision {
        if($cseType === EncryptionService::CSE_ENCRYPTION_NONE) $hash = sha1($password);

        $revisionModel = $this->createRevisionModel(
            $passwordId, $password, $login, $cseType, $sseType, $hash, $title, $url, $notes, $hidden, $trashed, $deleted,
            $favourite);

        $revisionModel = $this->validationService->validateRevision($revisionModel);

        return $revisionModel;
    }

    /**
     * @param Revision $revision
     *
     * @return Revision|\OCP\AppFramework\Db\Entity
     */
    public function saveRevision(Revision $revision): Revision {
        $revision = $this->encryptionService->encryptRevision($revision);

        if($revision->getStatus() == 0) {
            $revision->setStatus($this->securityCheck->getRevisionSecurityLevel($revision));
        }

        if(empty($revision->getId())) {
            return $this->revisionMapper->insert($revision);
        } else {
            $revision->setUpdated(time());

            return $this->revisionMapper->update($revision);
        }
    }

    /**
     * @param Revision $revision
     *
     * @return Revision
     */
    public function cloneRevision(Revision $revision): Revision {
        $clone = new Revision();
        $fields = array_keys($clone->getFieldTypes());

        foreach ($fields as $field) {
            if($field == 'id' || $field == 'uuid') continue;
            $clone->setProperty($field, $revision->getProperty($field));
        }

        $clone->setUuid($this->revisionMapper->generateUuidV4());
        $clone->setCreated(time());
        $clone->setUpdated(time());

        return $clone;
    }

    /**
     * @param string $passwordId
     * @param string $login
     * @param string $password
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $title
     * @param string $url
     * @param string $notes
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return Revision
     */
    protected function createRevisionModel(
        string $passwordId,
        string $password,
        string $login,
        string $cseType,
        string $sseType,
        string $hash,
        string $title,
        string $url,
        string $notes,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): Revision {

        $model = new Revision();
        $model->setDeleted(0);
        $model->setUser($this->user->getUID());
        $model->setUuid($this->revisionMapper->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());
        $model->setStatus(0);
        $model->setSseKey('');

        $model->setPasswordId($passwordId);
        $model->setLogin($login);
        $model->setPassword($password);
        $model->setCseType($cseType);
        $model->setSseType($sseType);
        $model->setHidden($hidden);
        $model->setDeleted($deleted);
        $model->setTrashed($trashed);
        $model->setHash($hash);
        $model->setTitle($title);
        $model->setUrl($url);
        $model->setNotes($notes);
        $model->setFavourite($favourite);

        return $model;
    }
}