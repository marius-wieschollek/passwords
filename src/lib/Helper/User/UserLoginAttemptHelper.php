<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\NotificationService;
use OCP\AppFramework\Http;

/**
 * Class UserLoginAttemptHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class UserLoginAttemptHelper {

    const MAX_FAILED_ATTEMPTS = 5;

    const CONFIG_LOGIN_ATTEMPTS = 'login/attempts/failed';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var ApiTokenHelper
     */
    protected $tokenHelper;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * UserLoginAttemptHelper constructor.
     *
     * @param EnvironmentService   $environment
     * @param ConfigurationService $config
     * @param LoggingService       $logger
     */
    public function __construct(EnvironmentService $environment, ConfigurationService $config, LoggingService $logger, ApiTokenHelper $tokenHelper, NotificationService $notifications) {
        $this->environment   = $environment;
        $this->config        = $config;
        $this->logger        = $logger;
        $this->tokenHelper   = $tokenHelper;
        $this->notifications = $notifications;
    }

    /**
     * @return bool
     */
    public function isAttemptAllowed(): bool {
        if($this->environment->getLoginType() === EnvironmentService::LOGIN_TOKEN) return true;

        try {
            $counter = intval($this->config->getUserValue(self::CONFIG_LOGIN_ATTEMPTS, 0));

            if($counter >= self::MAX_FAILED_ATTEMPTS) return false;
        } catch(Exception $e) {
            $this->logger->logException($e);
        }

        return true;
    }

    /**
     * @throws ApiException
     */
    public function registerFailedAttempt(): void {
        try {
            $counter = intval($this->config->getUserValue(self::CONFIG_LOGIN_ATTEMPTS, 0));
            $counter++;
            $this->config->setUserValue(self::CONFIG_LOGIN_ATTEMPTS, $counter);
        } catch(Exception $e) {
            $this->logger->logException($e);

            return;
        }

        if($counter >= self::MAX_FAILED_ATTEMPTS) {
            $revoked = false;
            $token   = $this->environment->getLoginToken();

            if($token !== null) {
                $this->tokenHelper->destroyToken($token->getId());
                $revoked = true;
            }

            $this->notifications->sendLoginAttemptNotification($this->environment->getUserId(), $this->environment->getClient(), $revoked);

            throw new ApiException('Too many failed login attempts', Http::STATUS_FORBIDDEN);
        }
    }

    /**
     *
     */
    public function registerSuccessfulAttempt(): void {
        try {
            $this->config->deleteUserValue(self::CONFIG_LOGIN_ATTEMPTS);
        } catch(Exception $e) {
            $this->logger->logException($e);
        }
    }
}