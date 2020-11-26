<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\EventListener\Challenge;

use Exception;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Events\Challenge\BeforeChallengeActivatedEvent;
use OCA\Passwords\Events\Challenge\ChallengeActivatedEvent;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\KeychainService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\ISecureRandom;

/**
 * Class ChallengeActivatedListener
 *
 * @package OCA\Passwords\EventListener\Challenge
 */
class ChallengeActivatedListener implements IEventListener {

    const MINIMUM_KEY_LENGTH = 1024;

    /**
     * @var UuidHelper
     */
    protected UuidHelper $uuidHelper;

    /**
     * @var ISecureRandom
     */
    protected ISecureRandom $secureRandom;

    /**
     * @var KeychainService
     */
    protected KeychainService $keychainService;

    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryptionService;

    /**
     * @var Keychain|null
     */
    private static ?Keychain $cseV1Keychain;

    /**
     * @var Keychain|null
     */
    private static ?Keychain $sseV2Keychain;

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
     * @param Event $event
     *
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function handle(Event $event): void {
        if($event instanceof BeforeChallengeActivatedEvent) {
            $this->beforeSetChallenge();
        } else if($event instanceof ChallengeActivatedEvent) {
            $this->afterSetChallenge();
        }
    }

    /**
     * @throws MultipleObjectsReturnedException
     */
    protected function beforeSetChallenge(): void {
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
     * @throws Exception
     */
    protected function afterSetChallenge(): void {
        if(!isset(self::$sseV2Keychain)) {
            self::$sseV2Keychain = $this->createSseV2Keychain();
        }

        $this->updateSseV2Keychain(self::$sseV2Keychain);

        if(isset(self::$cseV1Keychain)) $this->keychainService->save(self::$cseV1Keychain);
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
     * @throws Exception
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