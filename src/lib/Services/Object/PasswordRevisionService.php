<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\ValidationService;

/**
 * Class PasswordRevisionService
 *
 * @package OCA\Passwords\Services
 */
class PasswordRevisionService extends AbstractRevisionService {

    /**
     * @var PasswordRevisionMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = PasswordRevision::class;

    /**
     * PasswordRevisionService constructor.
     *
     * @param HookManager            $hookManager
     * @param EnvironmentService     $environment
     * @param PasswordRevisionMapper $revisionMapper
     * @param ValidationService      $validationService
     * @param EncryptionService      $encryptionService
     */
    public function __construct(
        HookManager $hookManager,
        EnvironmentService $environment,
        PasswordRevisionMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryptionService
    ) {
        parent::__construct($hookManager, $environment, $revisionMapper, $validationService, $encryptionService);
    }

    /**
     * @param string $model
     * @param string $password
     * @param string $username
     * @param string $cseKey
     * @param string $cseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $customFields
     * @param string $folder
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $favorite
     *
     * @return PasswordRevision
     *
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function create(
        string $model,
        string $password,
        string $username,
        string $cseKey,
        string $cseType,
        string $hash,
        string $label,
        string $url,
        string $notes,
        string $customFields,
        string $folder,
        int $edited,
        bool $hidden,
        bool $trashed,
        bool $favorite
    ): PasswordRevision {
        if($cseType === EncryptionService::CSE_ENCRYPTION_NONE) $hash = sha1($password);

        $revision = $this->createModel(
            $model, $password, $username, $cseKey, $cseType, $hash, $label, $url, $notes, $customFields, $folder, $edited, $hidden, $trashed, $favorite
        );

        $revision = $this->validationService->validatePassword($revision);
        $this->hookManager->emit($this->class, 'postCreate', [$revision]);

        return $revision;
    }

    /**
     * @param string $model
     * @param string $password
     * @param string $username
     * @param string $cseKey
     * @param string $cseType
     * @param string $hash
     * @param string $label
     * @param string $url
     * @param string $notes
     * @param string $customFields
     * @param string $folder
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $favorite
     *
     * @return PasswordRevision
     */
    protected function createModel(
        string $model,
        string $password,
        string $username,
        string $cseKey,
        string $cseType,
        string $hash,
        string $label,
        string $url,
        string $notes,
        string $customFields,
        string $folder,
        int $edited,
        bool $hidden,
        bool $trashed,
        bool $favorite
    ): PasswordRevision {

        $revision = new PasswordRevision();
        $revision->setUserId($this->userId);
        $revision->setUuid($this->generateUuidV4());
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->setStatus(0);
        $revision->setDeleted(false);
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setUsername($username);
        $revision->setPassword($password);
        $revision->setCseType($cseType);
        $revision->setCseKey($cseKey);
        $revision->setHidden($hidden);
        $revision->setTrashed($trashed);
        $revision->setHash($hash);
        $revision->setLabel($label);
        $revision->setUrl($url);
        $revision->setNotes($notes);
        $revision->setCustomFields($customFields);
        $revision->setFolder($folder);
        $revision->setFavorite($favorite);
        $revision->setEdited($edited);
        $revision->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);

        return $revision;
    }
}