<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Encryption\Object;

use Exception;
use OCA\Passwords\Exception\Encryption\SSEv3InvalidKeyException;
use OCA\Passwords\Exception\Encryption\SSEv3ProviderInvalidException;
use OCA\Passwords\Exception\Encryption\SSEv3ProviderNotAvailableException;
use OCA\Passwords\Exception\Encryption\SSEv3ProviderNotFoundException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\IAppContainer;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class SseV3Encryption extends SseV1Encryption {

    protected IAppContainer $container;

    /**
     * @param ICrypto              $crypto
     * @param ISecureRandom        $secureRandom
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     * @param IAppContainer        $container
     */
    public function __construct(ICrypto $crypto, ISecureRandom $secureRandom, ConfigurationService $config, EnvironmentService $environment, IAppContainer $container) {
        parent::__construct($crypto, $secureRandom, $config, $environment);
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return EncryptionService::SSE_ENCRYPTION_V3R1;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        try {
            return $this->getKeyProvider()?->isAvailable() === true;
        } catch(\Throwable $e) {
            return false;
        }
    }

    /**
     * @param string $passwordKey
     * @param string $userId
     *
     * @return string
     * @throws Exception
     */
    protected function getEncryptionKey(string $passwordKey, string $userId): string {
        return $this->getUserKey($userId).$passwordKey;
    }

    /**
     * Get the key for the current user from the third party key provider
     *
     * @param string $userId
     *
     * @return string
     * @throws Exception
     */
    protected function getUserKey(string $userId): string {
        if($this->userId !== $userId && $this->environment->getAppMode() !== EnvironmentService::MODE_GLOBAL) {
            throw new Exception('User key requested with illegal user id: '.$userId);
        }

        return $this->validateKey($this->getKeyProvider()->getUserKey($userId));
    }

    /**
     * Load the third party key provider
     *
     * @return SseV3KeyProviderInterface|null
     * @throws Exception
     */
    protected function getKeyProvider(): ?SseV3KeyProviderInterface {
        if(!$this->container->has(SseV3KeyProviderInterface::class)) {
            throw new SSEv3ProviderNotFoundException();
        }

        $provider = $this->container->get(SseV3KeyProviderInterface::class);
        if($provider instanceof SseV3KeyProviderInterface) {

            if(!$provider->isAvailable()) {
                throw new SSEv3ProviderNotAvailableException();
            }

            return $provider;
        }

        throw new SSEv3ProviderInvalidException();
    }

    /**
     * Check if the key provided by the third party provider is somewhat useful
     *
     * @param string $key
     *
     * @return string
     * @throws SSEv3InvalidKeyException
     */
    protected function validateKey(string $key) {
        if(strlen($key) < 32) {
            throw new SSEv3InvalidKeyException();
        }
        if(strlen(count_chars($key, 3)) < 8) {
            throw new SSEv3InvalidKeyException();
        }

        return $key;
    }
}