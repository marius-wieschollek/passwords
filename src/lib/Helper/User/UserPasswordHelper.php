<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCP\Security\IHasher;

class UserPasswordHelper {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var IHasher
     */
    protected $hasher;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * UserPasswordHelper constructor.
     *
     * @param IHasher              $hasher
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     * @param LoggingService       $logger
     */
    public function __construct(IHasher $hasher, ConfigurationService $config, EnvironmentService $environment, LoggingService $logger) {
        $this->hasher      = $hasher;
        $this->config      = $config;
        $this->environment = $environment;
        $this->logger      = $logger;
    }

    /**
     * @return bool
     */
    public function hasPassword(): bool {
        try {
            return $this->config->hasUserValue('user/account/password');
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }
    }

    /**
     * @return null|string
     */
    public function getPasswordAlgorithm(): ?string {
        try {
            return $this->config->getUserValue('user/account/password/algorithm', null);
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return null;
        }
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function validatePassword(string $password): bool {
        try {
            $hash = $this->config->getUserValue('user/account/password');
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }

        return $this->hasher->verify($this->makePassword($password), $hash);
    }

    /**
     * @param string $password
     * @param string $algorithm
     *
     * @return bool
     * @throws ApiException
     */
    public function setPassword(string $password, string $algorithm): bool {
        if(strlen($password) < 128 || !in_array($algorithm, ['BLAKE2b-64', 'SHA-512'])) {
            throw new ApiException('Invalid password');
        }
        try {
            $hash = $this->hasher->hash($this->makePassword($password));
            $this->config->setUserValue('user/account/password', $hash);
            $this->config->setUserValue('user/account/password/algorithm', $algorithm);

            return true;
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }
    }

    /**
     * @param string $password
     *
     * @return string
     */
    protected function makePassword(string $password): string {
        return $password.$this->config->getSystemValue('secret');
    }
}