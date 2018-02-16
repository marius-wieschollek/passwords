<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\Db\RevisionInterface;

/**
 * Interface EncryptionInterface
 *
 * @package OCA\Passwords\Encryption
 */
interface EncryptionInterface {

    const ENCRYPT_AES_256 = 'aes-256-cbc';

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     */
    public function encryptObject(RevisionInterface $object): RevisionInterface;

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     */
    public function decryptObject(RevisionInterface $object): RevisionInterface;
}