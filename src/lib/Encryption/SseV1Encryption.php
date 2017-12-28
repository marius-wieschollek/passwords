<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:27
 */

namespace OCA\Passwords\Encryption;

use Exception;
use OC;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\AbstractRevisionEntity;
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
    protected $password
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
     * @param AbstractRevisionEntity $object
     *
     * @return AbstractRevisionEntity
     * @throws \OCP\PreConditionNotMetException
     * @throws Exception
     */
    public function encryptObject(AbstractRevisionEntity $object): AbstractRevisionEntity {

        $sseKey        = $this->getSecureRandom();
        $encryptionKey = $this->getEncryptionKey($sseKey, $object->getUserId());

        $fields = $this->getFieldsToProcess($object);
        foreach ($fields as $field) {
            $value          = $object->getProperty($field);
            $encryptedValue = OC::$server->getCrypto()->encrypt($value, $encryptionKey);
            $object->setProperty($field, base64_encode($encryptedValue));
        }

        $object->setSseKey(base64_encode($sseKey));

        return $object;
    }

    /**
     * @param AbstractRevisionEntity $object
     *
     * @return AbstractRevisionEntity
     * @throws Exception
     */
    public function decryptObject(AbstractRevisionEntity $object): AbstractRevisionEntity {

        $sseKey        = base64_decode($object->getSseKey());
        $encryptionKey = $this->getEncryptionKey($sseKey, $object->getUserId());

        $fields = $this->getFieldsToProcess($object);
        foreach ($fields as $field) {
            $value          = base64_decode($object->getProperty($field));
            $decryptedValue = OC::$server->getCrypto()->decrypt($value, $encryptionKey);
            $object->setProperty($field, $decryptedValue);
        }

        return $object;
    }

    /**
     * @param $object
     *
     * @return array
     * @throws Exception
     */
    protected function getFieldsToProcess($object): array {
        switch (get_class($object)) {
            case PasswordRevision::class:
                return $this->password;
            case FolderRevision::class:
                return $this->folder;
            case TagRevision::class:
                return $this->tag;
        }

        throw new Exception('Unknown object type');
    }

    /**
     * @param string $passwordKey
     *
     * @param string $userId
     *
     * @return string
     * @throws \OCP\PreConditionNotMetException
     * @throws  Exception
     */
    protected function getEncryptionKey(string $passwordKey, string $userId): string {
        return base64_encode(
            $this->getServerKey().
            $this->getUserKey($userId).
            $passwordKey
        );
    }

    /**
     * @return string
     */
    protected function getServerKey(): string {
        $serverKey = OC::$server->getConfig()->getAppValue(Application::APP_NAME, 'SSEv1ServerKey', null);

        if($serverKey === null || strlen($serverKey) < self::MINIMUM_KEY_LENGTH) {
            $serverKey = $this->getSecureRandom();
            OC::$server->getConfig()->setAppValue(Application::APP_NAME, 'SSEv1ServerKey', $serverKey);
        }

        return $serverKey;
    }

    /**
     * @param string $userId
     *
     * @return string
     * @throws Exception
     * @throws \OCP\PreConditionNotMetException
     */
    protected function getUserKey(string $userId): string {
        $user    = OC::$server->getUserSession()->getUser();
        if($user !== null && $user->getUID() !== $userId) {
            throw new Exception('User key requested with illegal user id: '.$userId);
        }
        $userKey = OC::$server->getConfig()->getUserValue($userId, Application::APP_NAME, 'SSEv1UserKey', null);

        if($userKey === null || strlen($userKey) < self::MINIMUM_KEY_LENGTH) {
            $userKey = $this->getSecureRandom();
            OC::$server->getConfig()->setUserValue($userId, Application::APP_NAME, 'SSEv1UserKey', $userKey);
        }

        return $userKey;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function getSecureRandom(int $length = self::MINIMUM_KEY_LENGTH): string {
        if($length < self::MINIMUM_KEY_LENGTH) $length = self::MINIMUM_KEY_LENGTH;

        return OC::$server->getSecureRandom()->generate($length);
    }
}