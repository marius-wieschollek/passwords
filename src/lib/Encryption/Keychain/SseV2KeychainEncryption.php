<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Encryption\Keychain;

use Exception;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\SessionService;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

/**
 * Class SseV2KeychainEncryption
 *
 * @package OCA\Passwords\Encryption\Keychain
 */
class SseV2KeychainEncryption implements KeychainEncryptionInterface {
    /**
     * @var ICrypto
     */
    private $crypto;

    /**
     * @var ISecureRandom
     */
    protected $secureRandom;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * SseV2Encryption constructor.
     *
     * @param ICrypto              $crypto
     * @param ISecureRandom        $secureRandom
     * @param SessionService       $sessionService
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        ICrypto $crypto,
        ISecureRandom $secureRandom,
        SessionService $sessionService,
        ConfigurationService $config,
        EnvironmentService $environment
    ) {
        $this->crypto         = $crypto;
        $this->secureRandom   = $secureRandom;
        $this->sessionService = $sessionService;
        $this->config         = $config;
        $this->environment    = $environment;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        try {
            return $this->sessionService->has(SessionService::VALUE_USER_SECRET);
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getType(): string {
        return EncryptionService::SSE_ENCRYPTION_V2R1;
    }

    /**
     * @param Keychain $keychain
     *
     * @return Keychain
     */
    public function encryptKeychain(Keychain $keychain): Keychain {
        $encryptedData = $this->crypto->encrypt($keychain->getData(), $this->getEncryptionPassword());
        $keychain->setData($encryptedData);

        return $keychain;
    }

    /**
     * @param Keychain $keychain
     *
     * @return Keychain
     * @throws Exception
     */
    public function decryptKeychain(Keychain $keychain): Keychain {
        $decryptedData = $this->crypto->decrypt($keychain->getData(), $this->getEncryptionPassword());
        $keychain->setData($decryptedData);

        return $keychain;
    }

    /**
     * @return string
     */
    protected function getEncryptionPassword(): string {
        $userSecret   = $this->sessionService->get(SessionService::VALUE_USER_SECRET);
        $serverSecret = $this->config->getSystemValue('secret');

        return $serverSecret.$userSecret;
    }
}