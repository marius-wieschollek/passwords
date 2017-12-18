<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:27
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\AbstractEncryptedEntity;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;

/**
 * Class SseV1Encryption
 *
 * @package OCA\Passwords\Encryption
 */
class SseV1Encryption implements EncryptionInterface {

    const MINIMUM_KEY_LENGTH = 512;

    /**
     * @var array
     */
    protected $revision
        = [
            'url',
            'label',
            'notes',
            'password',
            'username'
        ];
    /**
     * @var array
     */
    protected $folder = ['label'];

    /**
     * @var array
     */
    protected $tag = ['label', 'color'];

    /**
     * @param PasswordRevision $revision
     *
     * @return PasswordRevision|AbstractEncryptedEntity
     */
    public function encryptRevision(PasswordRevision $revision): PasswordRevision {
        return $this->encryptObject($revision, 'revision');
    }

    /**
     * @param PasswordRevision $revision
     *
     * @return PasswordRevision|AbstractEncryptedEntity
     */
    public function decryptRevision(PasswordRevision $revision): PasswordRevision {
        return $this->decryptObject($revision, 'revision');
    }

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision|AbstractEncryptedEntity
     */
    public function encryptFolder(FolderRevision $folder): FolderRevision {
        return $this->encryptObject($folder, 'folder');
    }

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision|AbstractEncryptedEntity
     */
    public function decryptFolder(FolderRevision $folder): FolderRevision {
        return $this->decryptObject($folder, 'folder');
    }

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision|AbstractEncryptedEntity
     */
    public function encryptTag(TagRevision $tag): TagRevision {
        return $this->encryptObject($tag, 'tag');
    }

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision|AbstractEncryptedEntity
     * @throws \Exception
     */
    public function decryptTag(TagRevision $tag): TagRevision {
        return $this->decryptObject($tag, 'tag');
    }

    /**
     * @param AbstractEncryptedEntity $object
     * @param string                  $type
     *
     * @return AbstractEncryptedEntity
     * @throws \OCP\PreConditionNotMetException
     */
    public function encryptObject(AbstractEncryptedEntity $object, string $type): AbstractEncryptedEntity {

        $sseKey        = $this->getUserKey();
        $encryptionKey = $this->getEncryptionKey($sseKey);

        foreach ($this->{$type} as $field) {
            $value          = $object->getProperty($field);
            $encryptedValue = \OC::$server->getCrypto()->encrypt($value, $encryptionKey);
            $object->setProperty($field, base64_encode($encryptedValue));
        }

        $object->setSseKey(base64_encode($sseKey));

        return $object;
    }

    /**
     * @param AbstractEncryptedEntity $object
     * @param                         $type
     *
     * @return AbstractEncryptedEntity
     * @throws \Exception
     */
    public function decryptObject(AbstractEncryptedEntity $object, string $type): AbstractEncryptedEntity {

        $sseKey        = base64_decode($object->getSseKey());
        $encryptionKey = $this->getEncryptionKey($sseKey);

        foreach ($this->{$type} as $field) {
            $value          = base64_decode($object->getProperty($field));
            $decryptedValue = \OC::$server->getCrypto()->decrypt($value, $encryptionKey);
            $object->setProperty($field, $decryptedValue);
        }

        return $object;
    }

    /**
     * @param string $passwordKey
     *
     * @return string
     * @throws \OCP\PreConditionNotMetException
     */
    protected function getEncryptionKey(string $passwordKey): string {
        return base64_encode(
            $this->getServerKey().
            $this->getUserKey().
            $passwordKey
        );
    }

    /**
     * @return string
     */
    protected function getServerKey(): string {
        $serverKey = \OC::$server->getConfig()->getAppValue(Application::APP_NAME, 'SSEv1ServerKey', null);

        if($serverKey === null || strlen($serverKey) < self::MINIMUM_KEY_LENGTH) {
            $serverKey = $this->getSecureRandom();
            \OC::$server->getConfig()->setAppValue(Application::APP_NAME, 'SSEv1ServerKey', $serverKey);
        }

        return $serverKey;
    }

    /**
     * @return string
     * @throws \OCP\PreConditionNotMetException
     */
    protected function getUserKey(): string {
        $user    = \OC::$server->getUserSession()->getUser()->getUID();
        $userKey = \OC::$server->getConfig()->getUserValue($user, Application::APP_NAME, 'SSEv1UserKey', null);

        if($userKey === null || strlen($userKey) < self::MINIMUM_KEY_LENGTH) {
            $userKey = $this->getSecureRandom();
            \OC::$server->getConfig()->setUserValue($user, Application::APP_NAME, 'SSEv1UserKey', $userKey);
        }

        return $userKey;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function getSecureRandom(int $length = self::MINIMUM_KEY_LENGTH): string {
        if($length < self::MINIMUM_KEY_LENGTH) {
            $length = self::MINIMUM_KEY_LENGTH;
        }

        return \OC::$server->getSecureRandom()->generate($length);
    }
}