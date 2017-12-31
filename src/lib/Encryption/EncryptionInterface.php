<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:29
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