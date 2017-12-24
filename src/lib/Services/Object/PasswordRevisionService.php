<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 23:34
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Services\EncryptionService;

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