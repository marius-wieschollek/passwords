<?php

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\KeychainService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Security\ISecureRandom;

/**
 * Class ChallengeHook
 *
 * @package OCA\Passwords\Hooks
 */
class ChallengeHook {

    const MINIMUM_KEY_LENGTH = 1024;

    /**
     * @var UuidHelper
     */
    protected $uuidHelper;

    /**
     * @var ISecureRandom
     */
    protected $secureRandom;

    /**
     * @var KeychainService
     */
    protected $keychainService;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;

    /**
     * @var Keychain|null
     */
    private static $cseV1Keychain;

    /**
     * @var Keychain|null
     */
    private static $sseV2Keychain;

    /**
     * ChallengeHook constructor.
     *
     * @param UuidHelper        $uuidHelper
     * @param ISecureRandom     $secureRandom
     * @param KeychainService   $keychainService
     * @param EncryptionService $encryptionService
     */
    public function __construct(UuidHelper $uuidHelper, ISecureRandom $secureRandom, KeychainService $keychainService, EncryptionService $encryptionService) {
        $this->keychainService   = $keychainService;
        $this->encryptionService = $encryptionService;
        $this->uuidHelper        = $uuidHelper;
        $this->secureRandom      = $secureRandom;
    }

    /**
     * @throws MultipleObjectsReturnedException
     */
    public function preSetChallenge(): void {
        try {
            self::$cseV1Keychain = $this->keychainService->findByType(Keychain::TYPE_CSE_V1V1, true);
        } catch(DoesNotExistException $e) {
        }
        try {
            self::$sseV2Keychain = $this->keychainService->findByType(Keychain::TYPE_SSE_V2R1, true);
        } catch(DoesNotExistException $e) {
        }
    }

    /**
     * @throws \Exception
     */
    public function postSetChallenge(): void {
        if(self::$sseV2Keychain === null) {
            self::$sseV2Keychain = $this->createSseV2Keychain();
        }

        $this->updateSseV2Keychain(self::$sseV2Keychain);

        if(self::$cseV1Keychain !== null) $this->keychainService->save(self::$cseV1Keychain);
    }

    /**
     * @return Keychain
     */
    protected function createSseV2Keychain(): Keychain {
        return $this->keychainService->create(Keychain::TYPE_SSE_V2R1, '{"keys":[],"current":null}', Keychain::SCOPE_SERVER);
    }

    /**
     * @param Keychain $keychain
     *
     * @throws \Exception
     */
    protected function updateSseV2Keychain(Keychain $keychain): void {
        $uuid = $this->uuidHelper->generateUuid();

        $json                  = json_decode($keychain->getData(), true);
        $json['keys'][ $uuid ] = $this->getSecureRandom();
        $json['current']       = $uuid;

        $keychain->setData(json_encode($json));

        $this->keychainService->save($keychain);
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