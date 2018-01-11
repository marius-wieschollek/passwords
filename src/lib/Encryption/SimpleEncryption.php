<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.01.18
 * Time: 21:48
 */

namespace OCA\Passwords\Encryption;

/**
 * Class SimpleEncryption
 *
 * @package OCA\Passwords\Encryption
 */
class SimpleEncryption extends SseV1Encryption {

    /**
     * @param $string
     *
     * @return string
     * @throws \Exception
     * @throws \OCP\PreConditionNotMetException
     */
    public function encrypt($string): string {
        $encryptionKey = $this->getSimpleEncryptionKey($this->userId);

        return $this->crypto->encrypt($string, $encryptionKey);
    }

    /**
     * @param $string
     *
     * @return string
     * @throws \Exception
     * @throws \OCP\PreConditionNotMetException
     */
    public function decrypt($string): string {
        $encryptionKey = $this->getSimpleEncryptionKey($this->userId);

        return $this->crypto->decrypt($string, $encryptionKey);
    }

    /**
     * @param string $userId
     *
     * @return string
     * @throws \Exception
     * @throws \OCP\PreConditionNotMetException
     */
    protected function getSimpleEncryptionKey(string $userId): string {
        return $this->getServerKey().$this->getUserKey($userId);
    }
}