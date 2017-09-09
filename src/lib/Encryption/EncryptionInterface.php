<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:29
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\Db\Revision;

/**
 * Interface EncryptionInterface
 *
 * @package OCA\Passwords\Encryption
 */
interface EncryptionInterface {

    const ENCRYPT_AES_256 = 'aes-256-cbc';

    /**
     * Encrypt the values of the given entity
     *
     * @param Revision $revision
     *
     * @return Revision
     */
    public function encryptRevision(Revision $revision): Revision;

    /**
     * Decrypt the values of the given entity
     *
     * @param Revision $revision
     *
     * @return Revision
     */
    public function decryptRevision(Revision $revision): Revision;
}