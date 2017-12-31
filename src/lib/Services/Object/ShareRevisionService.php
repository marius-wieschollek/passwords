<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 15:08
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\ShareRevision;
use OCA\Passwords\Db\ShareRevisionMapper;
use OCA\Passwords\Services\EncryptionService;

/**
 * Class ShareRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
class ShareRevisionService extends AbstractRevisionService {

    /**
     * @var ShareRevisionMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = ShareRevision::class;

    /**
     * @param string $model
     * @param string $password
     * @param string $username
     * @param string $url
     * @param string $label
     * @param string $notes
     * @param string $hash
     * @param string $cseType
     * @param bool   $editable
     *
     * @return ShareRevision
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function create(
        string $model,
        string $password,
        string $username,
        string $url,
        string $label,
        string $notes,
        string $hash,
        string $cseType,
        bool $editable
    ): ShareRevision {
        if($cseType === EncryptionService::CSE_ENCRYPTION_NONE) $hash = sha1($password);

        $revision = $this->createModel(
            $model, $password, $username, $url, $label, $notes, $hash, $cseType, $editable
        );
        $revision = $this->validationService->validateShare($revision);
        $this->hookManager->emit($this->class, 'postCreate', [$revision]);

        return $revision;
    }

    /**
     * @param string $model
     * @param string $password
     * @param string $username
     * @param string $url
     * @param string $label
     * @param string $notes
     * @param string $hash
     * @param string $cseType
     * @param bool   $editable
     *
     * @return ShareRevision
     */
    protected function createModel(
        string $model,
        string $password,
        string $username,
        string $url,
        string $label,
        string $notes,
        string $hash,
        string $cseType,
        bool $editable
    ): ShareRevision {
        $revision = new ShareRevision();

        $revision->setUserId($this->userId);
        $revision->setUuid($this->generateUuidV4());
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->setDeleted(false);
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setPassword($password);
        $revision->setUsername($username);
        $revision->setCseType($cseType);
        $revision->setSseType(EncryptionService::DEFAULT_SHARE_ENCRYPTION);
        $revision->setHash($hash);
        $revision->setLabel($label);
        $revision->setUrl($url);
        $revision->setNotes($notes);
        $revision->setEditable($editable);
        $revision->setSynchronized(!$editable);
        $revision->setClient('');

        return $revision;
    }
}