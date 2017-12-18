<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:24
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Encryption\EncryptionInterface;
use OCA\Passwords\Encryption\SseV1Encryption;

/**
 * Class EncryptionService
 *
 * @package OCA\Passwords\Services
 */
class EncryptionService {

    const DEFAULT_CSE_ENCRYPTION = 'none';
    const DEFAULT_SSE_ENCRYPTION = 'SSEv1r1';
    const CSE_ENCRYPTION_NONE    = 'none';
    const SSE_ENCRYPTION_V1      = 'SSEv1r1';

    protected $encryptionMapping
        = [
            self::SSE_ENCRYPTION_V1 => SseV1Encryption::class
        ];

    /**
     * @param string $type
     *
     * @return EncryptionInterface
     * @throws \Exception
     */
    public function getEncryptionByType(string $type): EncryptionInterface {

        if(!isset($this->encryptionMapping[ $type ])) {
            throw new \Exception("Encryption type {$type} does not exsist");
        }

        return new $this->encryptionMapping[$type];
    }

    /**
     * @param PasswordRevision $password
     *
     * @return PasswordRevision
     * @throws \Exception
     */
    public function encryptRevision(PasswordRevision $password): PasswordRevision {
        $encryption = $this->getEncryptionByType($password->getSseType());
        $password->_setDecrypted(false);

        return $encryption->encryptRevision($password);
    }

    /**
     * @param PasswordRevision $password
     *
     * @return PasswordRevision
     * @throws \Exception
     */
    public function decryptRevision(PasswordRevision $password): PasswordRevision {
        $encryption = $this->getEncryptionByType($password->getSseType());
        $password->_setDecrypted(true);

        return $encryption->decryptRevision($password);
    }

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision
     * @throws \Exception
     */
    public function encryptFolder(FolderRevision $folder): FolderRevision {
        $encryption = $this->getEncryptionByType($folder->getSseType());
        $folder->_setDecrypted(false);

        return $encryption->encryptFolder($folder);
    }

    /**
     * @param FolderRevision $folder
     *
     * @return FolderRevision
     * @throws \Exception
     */
    public function decryptFolder(FolderRevision $folder): FolderRevision {
        $encryption = $this->getEncryptionByType($folder->getSseType());
        $folder->_setDecrypted(true);

        return $encryption->decryptFolder($folder);
    }

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision
     * @throws \Exception
     */
    public function encryptTag(TagRevision $tag): TagRevision {
        $encryption = $this->getEncryptionByType($tag->getSseType());
        $tag->_setDecrypted(false);

        return $encryption->encryptTag($tag);
    }

    /**
     * @param TagRevision $tag
     *
     * @return TagRevision
     * @throws \Exception
     */
    public function decryptTag(TagRevision $tag): TagRevision {
        $encryption = $this->getEncryptionByType($tag->getSseType());
        $tag->_setDecrypted(true);

        return $encryption->decryptTag($tag);
    }
}