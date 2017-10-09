<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:29
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\Db\AbstractEncryptedEntity;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\Revision;
use OCA\Passwords\Db\Tag;

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

    /**
     * @param Folder $folder
     *
     * @return Folder
     */
    public function encryptFolder(Folder $folder): Folder;

    /**
     * @param Folder $folder
     *
     * @return Folder
     */
    public function decryptFolder(Folder $folder): Folder;

    /**
     * @param Tag $tag
     *
     * @return Tag
     */
    public function encryptTag(Tag $tag): Tag;

    /**
     * @param Tag $tag
     *
     * @return Tag
     */
    public function decryptTag(Tag $tag): Tag;

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