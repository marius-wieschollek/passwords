<?php
/*
 * @copyright 2023 Passwords App
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
use OCA\Passwords\Helper\AppSettings\EncryptionSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Psr\Container\ContainerInterface;

class SseV3Encryption extends SseV1Encryption {

    /**
     * @param ICrypto                  $crypto
     * @param ISecureRandom            $secureRandom
     * @param ConfigurationService     $config
     * @param EnvironmentService       $environment
     * @param ContainerInterface       $container
     * @param EncryptionSettingsHelper $encryptionSettings
     */
    public function __construct(
        ICrypto                            $crypto,
        ISecureRandom                      $secureRandom,
        ConfigurationService               $config,
        EnvironmentService                 $environment,
        protected ContainerInterface       $container,
        protected EncryptionSettingsHelper $encryptionSettings
    ) {
        parent::__construct($crypto, $secureRandom, $config, $environment);
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
            return $this->encryptionSettings->get('ssev3.enabled') && $this->getKeyProvider(true)?->isAvailable() === true;
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
     * @param bool $noException
     *
     * @return SseV3KeyProviderInterface|null
     * @throws SSEv3ProviderInvalidException
     * @throws SSEv3ProviderNotAvailableException
     * @throws SSEv3ProviderNotFoundException
     */
    protected function getKeyProvider(bool $noException = false): ?SseV3KeyProviderInterface {
        if(!$this->container->has(SseV3KeyProviderInterface::class)) {
            if($noException) return null;
            throw new SSEv3ProviderNotFoundException();
        }

        $provider = $this->container->get(SseV3KeyProviderInterface::class);
        if($provider instanceof SseV3KeyProviderInterface) {
            if($noException) return $provider;

            if(!$provider->isAvailable()) {
                throw new SSEv3ProviderNotAvailableException();
            }

            return $provider;
        }

        if($noException) return null;
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
    protected function validateKey(string $key): string {
        if(strlen($key) < 32) {
            throw new SSEv3InvalidKeyException();
        }
        if(strlen(count_chars($key, 3)) < 8) {
            throw new SSEv3InvalidKeyException();
        }

        return $key;
    }
}