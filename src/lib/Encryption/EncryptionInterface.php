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

    /**
     * @return bool
     */
    public function isAvailable(): bool;

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