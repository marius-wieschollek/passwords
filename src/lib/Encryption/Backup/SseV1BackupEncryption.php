<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Encryption\Backup;

use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Encryption\Object\SseV1Encryption;

/**
 * Class SseV1BackupEncryption
 *
 * @package OCA\Passwords\Encryption\Backup
 */
class SseV1BackupEncryption extends SseV1Encryption {

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @param array $keys
     */
    public function setKeys(array $keys): void {
        $this->keys = $keys;
    }

    /**
     * @param array  $data
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function decryptArray(array $data, string $type = 'password'): array {
        $revision = $this->arrayToObject($data, $type);

        return $this->decryptObject($revision)->toArray();
    }

    /**
     * @param array  $data
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function encryptArray(array $data, string $type = 'password'): array {
        $revision = $this->arrayToObject($data, $type);

        return $this->encryptObject($revision)->toArray();
    }

    /**
     * @return string
     */
    protected function getServerKey(): string {
        return $this->keys['server']['SSEv1ServerKey'];
    }

    /**
     * @param string $userId
     *
     * @return string
     */
    protected function getUserKey(string $userId): string {
        return $this->keys['users'][ $userId ]['SSEv1UserKey'];
    }

    /**
     * @param array  $data
     * @param string $type
     *
     * @return FolderRevision|PasswordRevision|TagRevision
     * @throws \Exception
     */
    protected function arrayToObject(array $data, string $type) {
        if($type === 'password') {
            $revision = new PasswordRevision();
        } else if($type === 'folder') {
            $revision = new FolderRevision();
        } else if($type === 'tag') {
            $revision = new TagRevision();
        } else {
            throw new \Exception('Unknown object type');
        }

        foreach($data as $key => $value) {
            $revision->setProperty($key, $value);
        }

        return $revision;
    }
}