<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Encryption\Object;

/**
 * Class SimpleEncryption
 *
 * @package OCA\Passwords\Encryption
 */
class SimpleEncryption extends SseV1Encryption {

    /**
     * @return string
     */
    public function getType(): string {
        return 'SimpleEncryption';
    }

    /**
     * @param $string
     *
     * @return string
     * @throws \Exception
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
     */
    protected function getSimpleEncryptionKey(string $userId): string {
        return $this->getServerKey().$this->getUserKey($userId);
    }
}