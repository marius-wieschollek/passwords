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

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Provider\SecurityCheck\SecurityCheckProviderInterface;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\NotificationService;
use Throwable;

class PasswordDatabaseUpdateHelper {

    public const string CONFIG_UPDATE_ATTEMPTS = 'passwords/db/attempts';

    public const string CONFIG_UPDATE_ERROR_MESSAGE = 'passwords/db/error/message';

    public function __construct(
        protected SecurityCheckProviderInterface $securityCheckProvider,
        protected UserRulesSecurityCheck         $userRulesSecurityCheck,
        protected NotificationService            $notificationService,
        protected AdminUserHelper                $adminHelper,
        protected LoggingService                 $logger,
        protected ConfigurationService           $config,
    ) {
    }

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return bool
     */
    public function updateDb(): bool {
        if($this->securityCheckProvider->dbUpdateRequired()) {
            if(!$this->registerUpdateAttempt()) {
                return false;
            }
            try {
                $this->securityCheckProvider->updateDb();
                $this->registerUpdateSuccess();
            } catch(Throwable $e) {
                $this->registerUpdateFailure($e);

                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function registerUpdateAttempt(): bool {
        $attempts = $this->config->getAppValueInt(self::CONFIG_UPDATE_ATTEMPTS);
        $attempts++;
        $this->config->setAppValueInt(self::CONFIG_UPDATE_ATTEMPTS, $attempts);
        if($attempts > 3) {
            if($attempts === 4) {
                $this->sendUpdateFailureNotification('Too many failed attempts');
            } else if($attempts > 9) {
                /**
                 * Nearly a week has passed since the last attempt.
                 * Let's try again tomorrow.
                 */
                $this->config->deleteAppValue(self::CONFIG_UPDATE_ATTEMPTS);
            }

            return false;
        }

        return true;
    }

    /**
     * @param Throwable $e
     *
     * @return void
     */
    protected function registerUpdateFailure(Throwable $e): void {
        $this->logger->logException($e, [], 'Could not update breached passwords database: '.$e->getMessage());
        $this->config->setAppValue(self::CONFIG_UPDATE_ERROR_MESSAGE, $e->getMessage());
        $attempts = $this->config->getAppValueInt(self::CONFIG_UPDATE_ATTEMPTS);
        if($attempts >= 2) $this->sendUpdateFailureNotification($e->getMessage());
    }

    /**
     * @return void
     */
    protected function registerUpdateSuccess(): void {
        $this->config->deleteAppValue(self::CONFIG_UPDATE_ATTEMPTS);
        $this->config->deleteAppValue(self::CONFIG_UPDATE_ERROR_MESSAGE);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function sendUpdateFailureNotification(string $message): void {
        foreach($this->adminHelper->getAdmins() as $admin) {
            $this->notificationService->sendBreachedPasswordsUpdateFailedNotification($admin->getUID(), $message);
        }
    }
}