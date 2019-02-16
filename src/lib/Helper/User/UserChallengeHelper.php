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
use OCA\Passwords\Services\SessionService;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

/**
 * Class UserChallengeHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class UserChallengeHelper {
    /**
     * @var ICrypto
     */
    private $crypto;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var ISecureRandom
     */
    protected $secureRandom;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * UserChallengeHelper constructor.
     *
     * @param ICrypto              $crypto
     * @param LoggingService       $logger
     * @param ISecureRandom        $secureRandom
     * @param ConfigurationService $config
     * @param SessionService       $sessionService
     * @param EnvironmentService   $environment
     */
    public function __construct(
        ICrypto $crypto,
        LoggingService $logger,
        ISecureRandom $secureRandom,
        ConfigurationService $config,
        SessionService $sessionService,
        EnvironmentService $environment
    ) {
        $this->config         = $config;
        $this->environment    = $environment;
        $this->logger         = $logger;
        $this->secureRandom   = $secureRandom;
        $this->crypto         = $crypto;
        $this->sessionService = $sessionService;
    }

    /**
     * @return bool
     */
    public function hasChallenge(): bool {
        try {
            return $this->config->hasUserValue('user/challenge/challenge');
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }
    }

    /**
     * @return null|string
     */
    public function getChallenge(): ?string {
        try {
            return $this->config->getUserValue('user/challenge/challenge', null);
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return null;
        }
    }

    /**
     * @param string $password
     *
     * @return bool
     * @throws ApiException
     */
    public function validateChallenge(string $secret): bool {
        try {
            $encryptedKey = $this->config->getUserValue('user/challenge/key');
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }

        try {
            $key = $this->crypto->decrypt($encryptedKey, $this->makePassword($secret));
        } catch(\Exception $e) {
            throw new ApiException('Invalid Password');
        }

        $this->sessionService->set('userKey', $key);

        return true;
    }

    /**
     * @param string $challenge
     * @param string $secret
     *
     * @return bool
     * @throws ApiException
     */
    public function setChallenge(string $challenge, string $secret): bool {
        if(strlen($secret) < 512) {
            throw new ApiException('Secret too short');
        }
        try {
            $key          = $this->secureRandom->generate(512);
            $encryptedKey = $this->crypto->encrypt($key, $this->makePassword($secret));

            $this->config->setUserValue('user/challenge/challenge', $challenge);
            $this->config->setUserValue('user/challenge/key', $encryptedKey);

            $this->sessionService->set('userKey', $key);

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