<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Encryption\Object;

use Exception;
use OCA\Passwords\Exception\Encryption\InvalidEncryptionResultException;

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
     * @throws Exception
     */
    public function encrypt($string): string {
        $encryptionKey  = $this->getSimpleEncryptionKey($this->userId);
        $encryptedValue = $this->crypto->encrypt($string, $encryptionKey);

        if($string === $encryptedValue) {
            throw new InvalidEncryptionResultException();
        }

        return $encryptedValue;
    }

    /**
     * @param $string
     *
     * @return string
     * @throws Exception
     */
    public function decrypt($string): string {
        $encryptionKey = $this->getSimpleEncryptionKey($this->userId);

        return $this->crypto->decrypt($string, $encryptionKey);
    }

    /**
     * @param string $userId
     *
     * @return string
     * @throws Exception
     */
    protected function getSimpleEncryptionKey(string $userId): string {
        return $this->getServerKey().$this->getUserKey($userId);
    }
}