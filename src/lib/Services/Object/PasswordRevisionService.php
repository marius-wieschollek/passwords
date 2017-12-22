<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 23:34
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCP\IUser;

/**
 * Class PasswordRevisionService
 *
 * @package OCA\Passwords\Services
 */
class PasswordRevisionService extends AbstractService {

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
     * @var PasswordRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var AbstractSecurityCheckHelper
     */
    protected $securityCheck;

    /**
     * @var HookManager
     */
    protected $hookManager;

    /**
     * PasswordService constructor.
     *
     * @param IUser                       $user
     * @param HookManager                 $hookManager
     * @param ValidationService           $validationService
     * @param EncryptionService           $encryptionService
     * @param PasswordRevisionMapper      $revisionMapper
     * @param AbstractSecurityCheckHelper $securityCheck
     */
    public function __construct(
        IUser $user,
        HookManager $hookManager,
        ValidationService $validationService,
        EncryptionService $encryptionService,
        PasswordRevisionMapper $revisionMapper,
        AbstractSecurityCheckHelper $securityCheck
    ) {
        $this->user              = $user;
        $this->hookManager       = $hookManager;
        $this->securityCheck     = $securityCheck;
        $this->revisionMapper    = $revisionMapper;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param int  $id
     * @param bool $decrypt
     *
     * @return PasswordRevision
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function getRevisionById(int $id, bool $decrypt = true): PasswordRevision {
        /** @var PasswordRevision $revision */
        $revision = $this->revisionMapper->findById($id);
        if(!$decrypt) return $revision;

        return $this->encryptionService->decryptRevision($revision);
    }

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return PasswordRevision
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function getRevisionByUuid(string $uuid, bool $decrypt = true): PasswordRevision {
        /** @var PasswordRevision $revision */
        $revision = $this->revisionMapper->findByUuid($uuid);
        if(!$decrypt) return $revision;

        return $this->encryptionService->decryptRevision($revision);
    }

    /**
     * @param Password $password
     * @param bool     $decrypt
     *
     * @return PasswordRevision[]
     *
     * @throws \Exception
     */
    public function getRevisionsByPassword(Password $password, bool $decrypt = true): array {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionMapper->findAllMatching(['password', $password->getUuid()]);

        if(!$decrypt) return $revisions;

        foreach ($revisions as $revision) {
            $this->encryptionService->decryptRevision($revision);
        }

        return $revisions;
    }

    /**
     * @param Password $password
     * @param bool     $decrypt
     *
     * @return PasswordRevision
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getCurrentRevision(Password $password, bool $decrypt = true): PasswordRevision {
        /** @var PasswordRevision $revision */
        return $this->getRevisionByUuid($password->getRevision(), $decrypt);
    }

    /**
     * @param string $model
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $folder
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return PasswordRevision
     *
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function createRevision(
        string $model,
        string $password,
        string $username,
        string $cseType,
        string $sseType,
        string $hash,
        string $label,
        string $url,
        string $notes,
        string $folder,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): PasswordRevision {
        if($cseType === EncryptionService::CSE_ENCRYPTION_NONE) $hash = sha1($password);

        $revision = $this->createModel(
            $model, $password, $username, $cseType, $sseType, $hash, $label, $url, $notes, $folder, $hidden, $trashed, $deleted,
            $favourite
        );

        $revision = $this->validationService->validateRevision($revision);

        return $revision;
    }

    /**
     * @param PasswordRevision $revision
     *
     * @return PasswordRevision|\OCP\AppFramework\Db\Entity
     * @throws \Exception
     */
    public function saveRevision(PasswordRevision $revision): PasswordRevision {
        $this->hookManager->emit(PasswordRevision::class, 'preSave', [$revision]);

        if($revision->_isDecrypted()) {
            $revision = $this->encryptionService->encryptRevision($revision);

            // @TODO do not here
            if($revision->getStatus() == 0) {
                $revision->setStatus($this->securityCheck->getRevisionSecurityLevel($revision));
            }
        }

        if(empty($revision->getId())) {
            return $this->revisionMapper->insert($revision);
        } else {
            $revision->setUpdated(time());

            return $this->revisionMapper->update($revision);
        }
    }

    /**
     * @param PasswordRevision $revision
     * @param array            $overwrites
     *
     * @return PasswordRevision
     */
    public function cloneRevision(PasswordRevision $revision, array $overwrites = []): PasswordRevision {
        $this->hookManager->emit(PasswordRevision::class, 'preClone', [$revision]);
        /** @var PasswordRevision $clone */
        $clone = $this->cloneModel($revision, $overwrites);
        $this->hookManager->emit(PasswordRevision::class, 'postClone', [$revision, $clone]);

        return $clone;
    }

    /**
     * @param PasswordRevision $revision
     *
     * @throws \Exception
     */
    public function deleteRevision(PasswordRevision $revision): void {
        $this->hookManager->emit(PasswordRevision::class, 'preDelete', [$revision]);
        $revision->setDeleted(true);
        $this->saveRevision($revision);
        $this->hookManager->emit(PasswordRevision::class, 'postDelete', [$revision]);
    }

    /**
     * @param string $model
     * @param string $password
     * @param string $username
     * @param string $cseType
     * @param string $sseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $folder
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return PasswordRevision
     */
    protected function createModel(
        string $model,
        string $password,
        string $username,
        string $cseType,
        string $sseType,
        string $hash,
        string $label,
        string $url,
        string $notes,
        string $folder,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): PasswordRevision {

        $revision = new PasswordRevision();
        $revision->setDeleted(0);
        $revision->setUserId($this->user->getUID());
        $revision->setUuid($this->generateUuidV4());
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->setStatus(0);
        $revision->setSseKey('');
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setUsername($username);
        $revision->setPassword($password);
        $revision->setCseType($cseType);
        $revision->setSseType($sseType);
        $revision->setHidden($hidden);
        $revision->setDeleted($deleted);
        $revision->setTrashed($trashed);
        $revision->setHash($hash);
        $revision->setLabel($label);
        $revision->setUrl($url);
        $revision->setNotes($notes);
        $revision->setFolder($folder);
        $revision->setFavourite($favourite);

        return $revision;
    }
}