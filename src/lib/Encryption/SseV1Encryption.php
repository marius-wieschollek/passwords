<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:27
 */

namespace OCA\Passwords\Encryption;

use Exception;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\ConfigurationService;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

/**
 * Class SseV1Encryption
 *
 * @package OCA\Passwords\Encryption
 */
class SseV1Encryption implements EncryptionInterface {

    const MINIMUM_KEY_LENGTH = 1024;

    /**
     * @var array
     */
    protected $password
        = [
            'url',
            'label',
            'notes',
            'password',
            'username'
        ];

    /**
     * @var array
     */
    protected $folder = ['label'];

    /**
     * @var array
     */
    protected $tag = ['label', 'color'];

    /**
     * @var null|string
     */
    protected $userId;

    /**
     * @var ICrypto
     */
    protected $crypto;

    /**
     * @var ISecureRandom
     */
    protected $secureRandom;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * ShareV1Encryption constructor.
     *
     * @param null|string          $userId
     * @param ICrypto              $crypto
     * @param ISecureRandom        $secureRandom
     * @param ConfigurationService $configurationService
     */
    public function __construct(
        ?string $userId,
        ICrypto $crypto,
        ISecureRandom $secureRandom,
        ConfigurationService $configurationService
    ) {
        $this->userId               = $userId;
        $this->crypto               = $crypto;
        $this->secureRandom         = $secureRandom;
        $this->configurationService = $configurationService;
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \OCP\PreConditionNotMetException
     * @throws Exception
     */
    public function encryptObject(RevisionInterface $object): RevisionInterface {
        $sseKey        = $this->getSecureRandom();
        $encryptionKey = $this->getEncryptionKey($sseKey, $object->getUserId());

        $fields = $this->getFieldsToProcess($object);
        foreach($fields as $field) {
            $value          = $object->getProperty($field);
            $encryptedValue = $this->crypto->encrypt($value, $encryptionKey);
            $object->setProperty($field, $encryptedValue);
        }

        $object->setSseKey($sseKey);

        return $object;
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws Exception
     */
    public function decryptObject(RevisionInterface $object): RevisionInterface {
        $sseKey        = $object->getSseKey();
        $encryptionKey = $this->getEncryptionKey($sseKey, $object->getUserId());

        $fields = $this->getFieldsToProcess($object);
        foreach($fields as $field) {
            $value          = $object->getProperty($field);
            $decryptedValue = $this->crypto->decrypt($value, $encryptionKey);
            $object->setProperty($field, $decryptedValue);
        }

        return $object;
    }

    /**
     * @param $object
     *
     * @return array
     * @throws Exception
     */
    protected function getFieldsToProcess($object): array {
        switch(get_class($object)) {
            case PasswordRevision::class:
                return $this->password;
            case FolderRevision::class:
                return $this->folder;
            case TagRevision::class:
                return $this->tag;
        }

        throw new Exception('Unknown object type');
    }

    /**
     * @param string $passwordKey
     *
     * @param string $userId
     *
     * @return string
     * @throws \OCP\PreConditionNotMetException
     * @throws  Exception
     */
    protected function getEncryptionKey(string $passwordKey, string $userId): string {
        return $this->getServerKey().
               $this->getUserKey($userId).
               $passwordKey;
    }

    /**
     * @return string
     */
    protected function getServerKey(): string {
        $serverKey = $this->configurationService->getAppValue('SSEv1ServerKey', null);

        if($serverKey === null || strlen($serverKey) < self::MINIMUM_KEY_LENGTH) {
            $serverKey = $this->getSecureRandom();
            $this->configurationService->setAppValue('SSEv1ServerKey', $serverKey);
        }

        return $serverKey;
    }

    /**
     * @param string $userId
     *
     * @return string
     * @throws Exception
     * @throws \OCP\PreConditionNotMetException
     */
    protected function getUserKey(string $userId): string {
        if($this->userId !== null && $this->userId !== $userId && !$this->configurationService->getSystemValue('maintenance', false)) {
            throw new Exception('User key requested with illegal user id: '.$userId);
        }
        $userKey = $this->configurationService->getUserValue('SSEv1UserKey', null, $userId);

        if($userKey === null || strlen($userKey) < self::MINIMUM_KEY_LENGTH) {
            $userKey = $this->getSecureRandom();
            $this->configurationService->setUserValue('SSEv1UserKey', $userKey, $userId);
        }

        return $userKey;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function getSecureRandom(int $length = self::MINIMUM_KEY_LENGTH): string {
        if($length < self::MINIMUM_KEY_LENGTH) $length = self::MINIMUM_KEY_LENGTH;

        return $this->secureRandom->generate($length);
    }
}