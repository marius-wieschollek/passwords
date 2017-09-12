<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 23:34
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Revision;
use OCA\Passwords\Db\RevisionMapper;
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
     * PasswordService constructor.
     *
     * @param IUser             $user
     * @param ValidationService $validationService
     * @param EncryptionService $encryptionService
     * @param RevisionMapper    $revisionMapper
     */
    public function __construct(
        IUser $user,
        ValidationService $validationService,
        EncryptionService $encryptionService,
        RevisionMapper $revisionMapper
    ) {
        $this->user              = $user;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;
        $this->revisionMapper    = $revisionMapper;
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
     * @param int    $passwordId
     * @param string $login
     * @param string $password
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $title
     * @param string $url
     * @param string $notes
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @return Revision
     */
    public function createRevision(
        int $passwordId,
        string $login,
        string $password,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        string $sseType = EncryptionService::DEFAULT_SSE_ENCRYPTION,
        string $hash = '',
        string $title = '',
        string $url = '',
        string $notes = '',
        bool $hidden = false,
        bool $favourite = false
    ): Revision {
        if($cseType === EncryptionService::CSE_ENCRYPTION_NONE) $hash = sha1($password);

        $revisionModel = $this->createRevisionModel(
            $passwordId, $login, $password, $cseType, $sseType, $hash, $title, $url, $hidden, $notes, $favourite
        );

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

        if(empty($revision->getId())) {
            return $this->revisionMapper->insert($revision);
        } else {
            $revision->setUpdated(time());

            return $this->revisionMapper->update($revision);
        }
    }

    /**
     * @param int    $passwordId
     * @param string $login
     * @param string $password
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $title
     * @param string $url
     * @param int    $hidden
     * @param string $notes
     * @param bool   $favourite
     *
     * @return Revision
     */
    protected function createRevisionModel(
        int $passwordId,
        string $login,
        string $password,
        string $cseType,
        string $sseType,
        string $hash,
        string $title,
        string $url,
        int $hidden,
        string $notes,
        bool $favourite
    ): Revision {

        $model = new Revision();
        $model->setDeleted(0);
        $model->setUser($this->user->getUID());
        $model->setUuid($this->revisionMapper->generateUuidV4());
        $model->setCreated(time());
        $model->setUpdated(time());
        $model->setSecure(1);
        $model->setKey('');

        $model->setPasswordId($passwordId);
        $model->setLogin($login);
        $model->setPassword($password);
        $model->setCseType($cseType);
        $model->setSseType($sseType);
        $model->setHidden($hidden);
        $model->setHash($hash);
        $model->setTitle($title);
        $model->setUrl($url);
        $model->setNotes($notes);
        $model->setFavourite($favourite);

        return $model;
    }
}