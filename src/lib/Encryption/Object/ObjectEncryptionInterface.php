<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Encryption\Object;

use OCA\Passwords\Db\RevisionInterface;

/**
 * Interface ObjectEncryptionInterface
 *
 * @package OCA\Passwords\Encryption\Object
 */
interface ObjectEncryptionInterface {

    /**
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * @return string
     */
    public function getType(): string;

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