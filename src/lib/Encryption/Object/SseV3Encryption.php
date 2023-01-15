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
use OCA\Passwords\Services\ConfigurationService;
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
     * @param string $userId
     *
     * @return string
     * @throws Exception
     */
    protected function getUserKey(string $userId): string {
        if($this->userId !== $userId && $this->environment->getAppMode() !== EnvironmentService::MODE_GLOBAL) {
            throw new Exception('User key requested with illegal user id: '.$userId);
        }

        $provider = $this->getKeyProvider();
        if(!$provider->isAvailable()) {
            throw new Exception('User key provider not ready');
        }

        $userKey = $provider->getKey($userId);
        if(strlen($userKey) < 32) {
            throw new Exception('User key provider returned invalid key');
        }

        return $userKey;
    }

    /**
     * @return SseV3UserKeyProviderInterface|null
     * @throws Exception
     */
    protected function getKeyProvider(): ?SseV3UserKeyProviderInterface {
        if(!$this->container->has(SseV3UserKeyProviderInterface::class)) {
            return null;
        }

        $class = $this->container->get(SseV3UserKeyProviderInterface::class);
        if($class instanceof SseV3UserKeyProviderInterface) {
            return $class;
        }

        throw new Exception('Invalid SSEv3 User Key Provider');
    }
}