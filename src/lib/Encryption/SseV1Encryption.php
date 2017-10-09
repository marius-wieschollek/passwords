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
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\Revision;
use OCA\Passwords\Db\Tag;

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
            'title',
            'url',
            'login',
            'password',
            'notes'
        ];
    /**
     * @var array
     */
    protected $folder = ['name'];

    /**
     * @var array
     */
    protected $tag = ['name', 'color'];

    /**
     * @param Revision $revision
     *
     * @return Revision|AbstractEncryptedEntity
     */
    public function encryptRevision(Revision $revision): Revision {
        return $this->encryptObject($revision, 'revision');
    }

    /**
     * @param Revision $revision
     *
     * @return Revision|AbstractEncryptedEntity
     */
    public function decryptRevision(Revision $revision): Revision {
        return $this->decryptObject($revision, 'revision');
    }

    /**
     * @param Folder $folder
     *
     * @return Folder|AbstractEncryptedEntity
     */
    public function encryptFolder(Folder $folder): Folder {
        return $this->encryptObject($folder, 'folder');
    }

    /**
     * @param Folder $folder
     *
     * @return Folder|AbstractEncryptedEntity
     */
    public function decryptFolder(Folder $folder): Folder {
        return $this->decryptObject($folder, 'folder');
    }

    /**
     * @param Tag $tag
     *
     * @return Tag|AbstractEncryptedEntity
     */
    public function encryptTag(Tag $tag): Tag {
        return $this->encryptObject($tag, 'tag');
    }

    /**
     * @param Tag $tag
     *
     * @return Tag|AbstractEncryptedEntity
     */
    public function decryptTag(Tag $tag): Tag {
        return $this->decryptObject($tag, 'tag');
    }

    /**
     * @param AbstractEncryptedEntity $object
     * @param                         $type
     *
     * @return AbstractEncryptedEntity
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