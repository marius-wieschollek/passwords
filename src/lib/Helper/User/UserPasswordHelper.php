<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\Security\IHasher;

class UserPasswordHelper {
    /**
     * @var IHasher
     */
    private $hasher;
    /**
     * @var ConfigurationService
     */
    private $config;
    /**
     * @var EnvironmentService
     */
    private $environmentService;

    /**
     * UserPasswordHelper constructor.
     *
     * @param IHasher              $hasher
     * @param ConfigurationService $config
     * @param EnvironmentService   $environmentService
     */
    public function __construct(IHasher $hasher, ConfigurationService $config, EnvironmentService $environmentService) {
        $this->hasher = $hasher;
        $this->config = $config;
        $this->environmentService = $environmentService;
    }

    /**
     * @return bool
     */
    public function hasPassword(): bool {
        try {
            return $this->config->hasUserValue('user/account/password');
        } catch(\Exception $e) {
            return false;
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
            return false;
        }

        return $this->hasher->verify($this->makePassword($password), $hash);
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function setPassword(string $password): bool {
        try {
            $hash = $this->hasher->hash($this->makePassword($password));
            $this->config->setUserValue('user/account/password', $hash);

            return true;
        } catch(\Exception $e) {
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