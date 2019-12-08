<?php

namespace OCA\Passwords\Encryption\Object;

use Exception;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\SessionService;
use OCP\Security\ICrypto;
use function json_decode;

/**
 * Class SseV2Encryption
 *
 * @package OCA\Passwords\Encryption\Object
 */
class SseV2Encryption implements ObjectEncryptionInterface {

    /**
     * @var KeychainService
     */
    protected $keychainService;

    /**
     * @var UuidHelper
     */
    protected $uuidHelper;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var array|null
     */
    protected $keychainData;

    /**
     * @var ICrypto
     */
    protected $crypto;

    /**
     * @var array
     */
    protected $password
        = [
            'url',
            'label',
            'notes',
            'password',
            'username',
            'customFields'
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
     * @var bool|null
     */
    protected $isAvailable;

    /**
     * SseV2Encryption constructor.
     *
     * @param ICrypto         $crypto
     * @param UuidHelper      $uuidHelper
     * @param SessionService  $sessionService
     * @param KeychainService $keychainService
     */
    public function __construct(
        ICrypto $crypto,
        UuidHelper $uuidHelper,
        SessionService $sessionService,
        KeychainService $keychainService
    ) {
        $this->keychainService = $keychainService;
        $this->uuidHelper      = $uuidHelper;
        $this->sessionService  = $sessionService;
        $this->crypto          = $crypto;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        if($this->isAvailable !== null) return $this->isAvailable;

        try {
            if(!$this->sessionService->has(SessionService::VALUE_USER_SECRET)) {
                $this->isAvailable = false;

                return false;
            }

            $this->keychainService->findByType(Keychain::TYPE_SSE_V2R1);
        } catch(Exception $e) {
            $this->isAvailable = false;

            return false;
        }

        $this->isAvailable = true;

        return true;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return EncryptionService::SSE_ENCRYPTION_V2R1;
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws Exception
     */
    public function encryptObject(RevisionInterface $object): RevisionInterface {
        list($keyId, $encryptionKey) = $this->getCurrentKey();

        $fields = $this->getFieldsToProcess($object);
        foreach($fields as $field) {
            $value          = $object->getProperty($field);
            $encryptedValue = $this->crypto->encrypt($value, $encryptionKey);
            $object->setProperty($field, $encryptedValue);
        }

        $object->setSseKey($keyId);
        $object->setSseType(EncryptionService::SSE_ENCRYPTION_V2R1);

        return $object;
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws Exception
     */
    public function decryptObject(RevisionInterface $object): RevisionInterface {
        $encryptionKey = $this->getKey($object->getSseKey());

        $fields = $this->getFieldsToProcess($object);
        foreach($fields as $field) {
            $value = $object->getProperty($field);
            if($value === null) continue;

            $decryptedValue = $this->crypto->decrypt($value, $encryptionKey);
            $object->setProperty($field, $decryptedValue);
        }

        return $object;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getCurrentKey(): array {
        $keys = $this->getKeychainData();
        $id   = $keys['current'];

        if(isset($keys['keys'][ $id ])) return [$id, $keys['keys'][ $id ]];

        throw new \Exception('Current key not found in Keychain');
    }

    /**
     * @param string $id
     *
     * @return string
     * @throws Exception
     */
    protected function getKey(string $id): string {
        $keys = $this->getKeychainData()['keys'];
        if(isset($keys[ $id ])) return $keys[ $id ];

        throw new \Exception('Key not found in Keychain');
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getKeychainData(): array {
        if($this->keychainData === null) {
            $keychain           = $this->keychainService->findByType(Keychain::TYPE_SSE_V2R1, true);
            $this->keychainData = json_decode($keychain->getData(), true);
        }

        return $this->keychainData;
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
}