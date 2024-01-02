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

    protected const CONFIG_UPDATE_ATTEMPTS = 'passwords/db/attempts';

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
            };
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

    protected function registerUpdateAttempt() {
        $attempts = intval($this->config->getAppValue(self::CONFIG_UPDATE_ATTEMPTS, 0));
        $attempts++;
        if($attempts >= 3) {
            $this->sendUpdateFailureNotification('Too many failed attempts');

            return false;
        }
        $this->config->setAppValue(self::CONFIG_UPDATE_ATTEMPTS, $attempts);
        return true;
    }

    /**
     * @param Throwable $e
     *
     * @return void
     */
    protected function registerUpdateFailure(Throwable $e): void {
        $this->logger->logException($e, [], 'Could not update breached passwords database: '.$e->getMessage());
        $attempts = intval($this->config->getAppValue(self::CONFIG_UPDATE_ATTEMPTS, 0));
        if($attempts >= 3) $this->sendUpdateFailureNotification($e->getMessage());
    }

    /**
     * @return void
     */
    protected function registerUpdateSuccess(): void {
        $this->config->deleteAppValue(self::CONFIG_UPDATE_ATTEMPTS);
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