<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Encryption\Challenge\ChallengeEncryptionInterface;
use OCA\Passwords\Encryption\Challenge\SimpleChallengeEncryption;
use OCA\Passwords\Encryption\Object\ObjectEncryptionInterface;
use OCA\Passwords\Encryption\Keychain\KeychainEncryptionInterface;
use OCA\Passwords\Encryption\Keychain\SseV2KeychainEncryption;
use OCA\Passwords\Encryption\Object\SseV1Encryption;
use OCA\Passwords\Encryption\Object\SseV2Encryption;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCP\AppFramework\IAppContainer;

/**
 * Class EncryptionService
 *
 * @package OCA\Passwords\Services
 */
class EncryptionService {

    const DEFAULT_CSE_ENCRYPTION   = 'none';
    const DEFAULT_SSE_ENCRYPTION   = 'SSEv1r2';
    const DEFAULT_SHARE_ENCRYPTION = 'SSSEv1r1';
    const CSE_ENCRYPTION_NONE      = 'none';
    const CSE_ENCRYPTION_V1R1      = 'CSEv1r1';
    const SSE_ENCRYPTION_NONE      = 'none';
    const SSE_ENCRYPTION_V1R1      = 'SSEv1r1';
    const SSE_ENCRYPTION_V1R2      = 'SSEv1r2';
    const SSE_ENCRYPTION_V2R1      = 'SSEv2r1';
    const SHARE_ENCRYPTION_V1      = 'SSSEv1r1';

    protected $objectMapping
        = [
            self::SSE_ENCRYPTION_V1R1 => SseV1Encryption::class,
            self::SSE_ENCRYPTION_V1R2 => SseV1Encryption::class,
            self::SSE_ENCRYPTION_V2R1 => SseV2Encryption::class,
        ];

    protected $keychainMapping
        = [
            Keychain::TYPE_CSE_V1V1 => SseV2KeychainEncryption::class,
            Keychain::TYPE_SSE_V2R1 => SseV2KeychainEncryption::class
        ];

    protected $challengeMapping
        = [
            Challenge::TYPE_PWD_V1R1 => SimpleChallengeEncryption::class
        ];

    /**
     * @var IAppContainer
     */
    private $container;

    /**
     * @var UserSettingsHelper
     */
    protected $userSettings;

    /**
     * EncryptionService constructor.
     *
     * @param IAppContainer $container
     */
    public function __construct(IAppContainer $container, UserSettingsHelper $userSettings) {
        $this->container = $container;
        $this->userSettings = $userSettings;
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function encrypt(RevisionInterface $object): RevisionInterface {
        if(!$object->_isDecrypted()) return $object;

        $object->_setDecrypted(false);
        if($object->getSseType() === self::SSE_ENCRYPTION_NONE) return $object;
        $encryption = $this->getObjectEncryptionByType($object->getSseType());

        if(!$encryption->isAvailable()) throw new \Exception("Object encryption type {$encryption->getType()} is not available");

        return $encryption->encryptObject($object);
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function decrypt(RevisionInterface $object): RevisionInterface {
        if($object->_isDecrypted()) return $object;

        $object->_setDecrypted(true);
        if($object->getSseType() === self::SSE_ENCRYPTION_NONE) return $object;
        $encryption = $this->getObjectEncryptionByType($object->getSseType());

        if(!$encryption->isAvailable()) throw new \Exception("Object encryption type {$encryption->getType()} is not available");

        return $encryption->decryptObject($object);
    }

    /**
     * @param Keychain $keychain
     *
     * @return Keychain
     * @throws \Exception
     */
    public function encryptKeychain(Keychain $keychain): Keychain {
        if(!$keychain->_isDecrypted()) return $keychain;

        $encryption = $this->getKeychainEncryptionByType($keychain->getType());
        if(!$encryption->isAvailable()) throw new \Exception("Keychain encryption type {$encryption->getType()} is not available");
        $keychain->_setDecrypted(false);

        return $encryption->encryptKeychain($keychain);
    }

    /**
     * @param Keychain $keychain
     *
     * @return Keychain
     * @throws \Exception
     */
    public function decryptKeychain(Keychain $keychain): Keychain {
        if($keychain->_isDecrypted()) return $keychain;

        $encryption = $this->getKeychainEncryptionByType($keychain->getType());
        if(!$encryption->isAvailable()) throw new \Exception("Keychain encryption type {$encryption->getType()} is not available");
        $keychain->_setDecrypted(true);

        return $encryption->decryptKeychain($keychain);
    }

    /**
     * @param Challenge $challenge
     *
     * @return Challenge
     * @throws \Exception
     */
    public function encryptChallenge(Challenge $challenge): Challenge {
        if(!$challenge->_isDecrypted()) return $challenge;

        $encryption = $this->getChallengeEncryptionByType($challenge->getType());
        if(!$encryption->isAvailable()) throw new \Exception("Challenge encryption type {$encryption->getType()} is not available");
        $challenge->_setDecrypted(false);

        return $encryption->encryptChallenge($challenge);
    }

    /**
     * @param Challenge $challenge
     *
     * @return Challenge
     * @throws \Exception
     */
    public function decryptChallenge(Challenge $challenge): Challenge {
        if($challenge->_isDecrypted()) return $challenge;

        $encryption = $this->getChallengeEncryptionByType($challenge->getType());
        if(!$encryption->isAvailable()) throw new \Exception("Challenge encryption type {$encryption->getType()} is not available");
        $challenge->_setDecrypted(true);

        return $encryption->decryptChallenge($challenge);
    }

    /**
     * @param string|null $cseType
     *
     * @param string|null $userId
     *
     * @return string
     * @throws Exception
     */
    public function getDefaultEncryption(string $cseType = null, string $userId = null): string {
        $sseMode = $this->userSettings->get('encryption.sse', $userId);

        if($sseMode === 0 && $cseType !== self::CSE_ENCRYPTION_NONE) return self::SSE_ENCRYPTION_NONE;
        if($sseMode === 1) return self::SSE_ENCRYPTION_V1R2;
        if($sseMode === 2 && $this->getObjectEncryptionByType(self::SSE_ENCRYPTION_V2R1)->isAvailable()) {
            return self::SSE_ENCRYPTION_V2R1;
        }

        return self::SSE_ENCRYPTION_V1R2;
    }

    /**
     * @param string $type
     *
     * @return ObjectEncryptionInterface
     * @throws \Exception
     */
    protected function getObjectEncryptionByType(string $type): ObjectEncryptionInterface {

        if(!isset($this->objectMapping[ $type ])) {
            throw new \Exception("Object encryption type {$type} does not exist");
        }

        return $this->container->query($this->objectMapping[ $type ]);
    }

    /**
     * @param string $type
     *
     * @return KeychainEncryptionInterface
     * @throws \Exception
     */
    protected function getKeychainEncryptionByType(string $type): KeychainEncryptionInterface {

        if(!isset($this->keychainMapping[ $type ])) {
            throw new \Exception("Keychain encryption not found for {$type}");
        }

        return $this->container->query($this->keychainMapping[ $type ]);
    }

    /**
     * @param string $type
     *
     * @return ChallengeEncryptionInterface
     * @throws \Exception
     */
    protected function getChallengeEncryptionByType(string $type): ChallengeEncryptionInterface {

        if(!isset($this->challengeMapping[ $type ])) {
            throw new \Exception("Challenge encryption not found for {$type}");
        }

        return $this->container->query($this->challengeMapping[ $type ]);
    }
}