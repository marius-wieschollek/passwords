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