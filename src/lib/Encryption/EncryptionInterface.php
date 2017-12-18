<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:29
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\Db\AbstractEncryptedEntity;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;

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
     * @param PasswordRevision $revision
     *
     * @return PasswordRevision
     */
    public function encryptRevision(PasswordRevision $revision): PasswordRevision;

    /**
     * Decrypt the values of the given entity
     *
     * @param PasswordRevision $revision
     *
     * @return PasswordRevision
     */
    public function decryptRevision(PasswordRevision $revision): PasswordRevision;

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision
     */
    public function encryptFolder(FolderRevision $folder): FolderRevision;

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision
     */
    public function decryptFolder(FolderRevision $folder): FolderRevision;

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision
     */
    public function encryptTag(TagRevision $tag): TagRevision;

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision
     */
    public function decryptTag(TagRevision $tag): TagRevision;

    /**
     * @param AbstractEncryptedEntity $object
     * @param string                  $type
     *
     * @return AbstractEncryptedEntity
     */
    public function encryptObject(AbstractEncryptedEntity $object, string $type): AbstractEncryptedEntity;

    /**
     * @param AbstractEncryptedEntity $object
     * @param string                  $type
     *
     * @return AbstractEncryptedEntity
     */
    public function decryptObject(AbstractEncryptedEntity $object, string $type): AbstractEncryptedEntity;
}