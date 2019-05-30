<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\SessionService;
use OCP\AppFramework\Http;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

/**
 * Class UserChallengeHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class UserChallengeHelper {

    const USER_SECRET_KEY = 'user/secret/key';

    const USER_SECRET_SALTS = 'user/secret/salts';

    const USER_SECRET_CRYPT_KEY = 'user/secret/cryptKey';

    /**
     * @var ICrypto
     */
    protected $crypto;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var HookManager
     */
    protected $hookManager;

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
        HookManager $hookManager,
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
        $this->hookManager    = $hookManager;
    }

    /**
     * @return bool
     */
    public function hasChallenge(): bool {
        try {
            return $this->config->hasUserValue(self::USER_SECRET_KEY);
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }
    }

    /**
     * @return null|array
     * @throws \Exception
     */
    public function getSalts(): ?array {
        try {
            $encrypted = $this->config->getUserValue(self::USER_SECRET_SALTS, '[]');
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return null;
        }

        $decrypted = $this->crypto->decrypt($encrypted, $this->makePassword(''));

        return json_decode($decrypted, true);
    }

    /**
     * @param string $secret
     *
     * @return bool
     * @throws ApiException
     */
    public function validateChallenge(string $secret): bool {
        if(strlen($secret) !== 64) {
            throw new ApiException('Secret length invalid', HTTP::STATUS_BAD_REQUEST);
        }

        try {
            $encryptedKey = $this->config->getUserValue(self::USER_SECRET_KEY);
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }

        try {
            $key = $this->crypto->decrypt($encryptedKey, $this->makePassword($secret));
        } catch(\Exception $e) {
            throw new ApiException('Password invalid', HTTP::STATUS_UNAUTHORIZED);
        }

        $this->sessionService->set(SessionService::VALUE_USER_SECRET, $key);

        return true;
    }

    /**
     * @param array  $salts
     * @param string $secret
     *
     * @return bool
     * @throws ApiException
     */
    public function setChallenge(array $salts, string $secret): bool {
        if(strlen($secret) !== 64) {
            throw new ApiException('Secret length invalid', HTTP::STATUS_BAD_REQUEST);
        }

        if(strlen($salts[0]) < 512 || strlen($salts[1]) !== 128 || strlen($salts[2]) !== 32) {
            throw new ApiException('Salt length invalid', HTTP::STATUS_BAD_REQUEST);
        }

        $backup = $this->backupChallenge();
        try {
            $this->hookManager->emit('\OCA\Passwords\User\Challenge', 'preSetChallenge');
            $key = $this->updateSaltAndKey($salts, $secret);
            $this->sessionService->set(SessionService::VALUE_USER_SECRET, $key);
            $this->hookManager->emit('\OCA\Passwords\User\Challenge', 'postSetChallenge', [$key]);

            return true;
        } catch(\Exception $e) {
            $this->logger->logException($e);

            $this->revertChallenge($backup);

            return false;
        }
    }

    /**
     * @param string $password
     *
     * @return string
     * @throws \Exception
     */
    protected function makePassword(string $password): string {
        $this->config->clearCache();
        $cryptKey = $this->config->getUserValue(self::USER_SECRET_CRYPT_KEY, '');
        if(strlen($cryptKey) < 512) {
            $cryptKey = $this->getSecureRandom();
            $this->config->setUserValue(self::USER_SECRET_CRYPT_KEY, $cryptKey);
        }

        return $this->config->getSystemValue('secret').$cryptKey.$password;
    }

    /**
     * @return string
     */
    protected function getSecureRandom(): string {
        return $this->secureRandom->generate(512);
    }

    /**
     * @param array  $salts
     * @param string $secret
     *
     * @return string
     * @throws \Exception
     */
    protected function updateSaltAndKey(array $salts, string $secret): string {
        $json           = json_encode($salts);
        $encryptedSalts = $this->crypto->encrypt($json, $this->makePassword(''));;
        $this->config->setUserValue(self::USER_SECRET_SALTS, $encryptedSalts);

        $key          = $this->getSecureRandom();
        $encryptedKey = $this->crypto->encrypt($key, $this->makePassword($secret));
        $this->config->setUserValue(self::USER_SECRET_KEY, $encryptedKey);

        return $key;
    }

    /**
     * @return array
     * @throws ApiException
     */
    protected function backupChallenge(): array {
        $backup = [];
        if($this->hasChallenge()) {
            try {
                $backup = [
                    'salts'   => $this->config->getUserValue(self::USER_SECRET_SALTS),
                    'key'     => $this->config->getUserValue(self::USER_SECRET_KEY),
                    'secret' => $this->sessionService->get(SessionService::VALUE_USER_SECRET)
                ];
            } catch(Exception $e) {
                $this->logger->logException($e);

                throw new ApiException('Password update failed');
            }
        }

        return $backup;
    }

    /**
     * @param $backup
     */
    protected function revertChallenge(array $backup): void {
        try {
            if(isset($backup['salts'])) {
                $this->config->setUserValue(self::USER_SECRET_SALTS, $backup['salts']);
                $this->config->setUserValue(self::USER_SECRET_KEY, $backup['key']);
                $this->sessionService->set(SessionService::VALUE_USER_SECRET, $backup['secret']);
            } else {
                $this->config->deleteUserValue(self::USER_SECRET_SALTS);
                $this->config->deleteUserValue(self::USER_SECRET_KEY);
                $this->sessionService->unset(SessionService::VALUE_USER_SECRET);
            }
        } catch(Exception $e) {
            $this->logger->logException($e);
        }
    }
}