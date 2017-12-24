<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:29
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\Db\AbstractRevisionEntity;

/**
 * Interface EncryptionInterface
 *
 * @package OCA\Passwords\Encryption
 */
interface EncryptionInterface {

    const ENCRYPT_AES_256 = 'aes-256-cbc';

    /**
     * @param AbstractRevisionEntity $object
     *
     * @return AbstractRevisionEntity
     */
    public function encryptObject(AbstractRevisionEntity $object): AbstractRevisionEntity;

    /**
     * @param AbstractRevisionEntity $object
     *
     * @return AbstractRevisionEntity
     */
    public function decryptObject(AbstractRevisionEntity $object): AbstractRevisionEntity;
}