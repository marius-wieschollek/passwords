<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:27
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\Revision;

/**
 * Class SseV1Encryption
 *
 * @package OCA\Passwords\Encryption
 */
class SseV1Encryption implements \OCA\Passwords\Encryption\EncryptionInterface {

    const MINIMUM_KEY_LENGTH = 512;

    /**
     * @var array
     */
    protected $revisionFields
        = [
            'title',
            'url',
            'login',
            'password',
            'notes'
        ];

    /**
     * @param Revision $revision
     *
     * @return Revision
     */
    public function encryptRevision(Revision $revision): Revision {

        $passwordKey   = $this->getUserKey();
        $encryptionKey = $this->getEncryptionKey($passwordKey);

        foreach ($this->revisionFields as $field) {
            $value          = $revision->getProperty($field);
            $encryptedValue = \OC::$server->getCrypto()->encrypt($value, $encryptionKey);
            $revision->setProperty($field, base64_encode($encryptedValue));
        }

        $revision->setKey(base64_encode($passwordKey));

        return $revision;
    }

    /**
     * @param Revision $revision
     *
     * @return Revision
     */
    public function decryptRevision(Revision $revision): Revision {

        $passwordKey   = base64_decode($revision->getKey());
        $encryptionKey = $this->getEncryptionKey($passwordKey);

        foreach ($this->revisionFields as $field) {
            $value          = base64_decode($revision->getProperty($field));
            $decryptedValue = \OC::$server->getCrypto()->decrypt($value, $encryptionKey);
            $revision->setProperty($field, $decryptedValue);
        }

        return $revision;
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