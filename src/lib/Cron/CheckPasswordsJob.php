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

namespace OCA\Passwords\Cron;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Exception\Database\DecryptedDataException;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\MailService;
use OCA\Passwords\Services\NotificationService;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Class CheckPasswordsJob
 *
 * @package OCA\Passwords\Cron
 */
class CheckPasswordsJob extends AbstractTimedJob {

    const CONFIG_UPDATE_ATTEMPTS = 'passwords/db/attempts';

    /**
     * @var array
     */
    protected array $hashLengths = [];

    /**
     * @var array
     */
    protected array $badPasswords = [];

    /**
     * CheckPasswordsJob constructor.
     *
     * @param ITimeFactory           $time
     * @param LoggingService         $logger
     * @param MailService            $mailService
     * @param ConfigurationService   $config
     * @param HelperService          $helperService
     * @param EnvironmentService     $environment
     * @param UserSettingsHelper     $userSettingsHelper
     * @param PasswordRevisionMapper $revisionMapper
     * @param NotificationService    $notificationService
     * @param AdminUserHelper        $adminHelper
     */
    public function __construct(
        ITimeFactory $time,
        LoggingService                   $logger,
        protected MailService            $mailService,
        ConfigurationService             $config,
        protected HelperService          $helperService,
        EnvironmentService               $environment,
        protected UserSettingsHelper     $userSettingsHelper,
        protected PasswordRevisionMapper $revisionMapper,
        protected NotificationService    $notificationService,
        protected AdminUserHelper        $adminHelper
    ) {
        parent::__construct($time, $logger, $config, $environment);
        $this->setInterval(24 * 60 * 60);
        $this->setTimeSensitivity(self::TIME_INSENSITIVE);
    }

    /**
     * @param $argument
     *
     * @throws Throwable
     */
    protected function runJob($argument): void {
        $securityHelper = $this->helperService->getSecurityHelper();

        if($securityHelper->dbUpdateRequired()) {
            $this->registerUpdateAttempt();
            try {
                $securityHelper->updateDb();
                $this->config->deleteAppValue(self::CONFIG_UPDATE_ATTEMPTS);
            } catch(Throwable $e) {
                $this->registerUpdateFailure($e);
                throw $e;
            }
            $this->registerUpdateSuccess();
        }

        $this->checkRevisionStatus($securityHelper);
    }

    /**
     * @param AbstractSecurityCheckHelper $securityHelper
     *
     * @throws Exception
     */
    protected function checkRevisionStatus(AbstractSecurityCheckHelper $securityHelper): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionMapper->findAll();

        $badRevisionCounter = 0;
        foreach($revisions as $revision) {
            $this->checkHashLength($revision);

            if($revision->getStatus() === AbstractSecurityCheckHelper::LEVEL_BAD || $revision->getStatus() === AbstractSecurityCheckHelper::LEVEL_UNKNOWN) continue;

            $oldStatusCode = $revision->getStatusCode();
            [$statusLevel, $statusCode] = $securityHelper->getRevisionSecurityLevel($revision);

            if($oldStatusCode !== $statusCode) {
                $revision->setStatus($statusLevel);
                $revision->setStatusCode($statusCode);
                $revision->setUpdated(time());
                $this->revisionMapper->update($revision);

                if($statusLevel === AbstractSecurityCheckHelper::LEVEL_BAD) {
                    $this->sendBadPasswordNotification($revision);
                    $badRevisionCounter++;
                }
            }
        }

        $this->notifyUsers();
        $this->logger->debugOrInfo(['Checked %s passwords. %s new bad revisions found', count($revisions), $badRevisionCounter], $badRevisionCounter);
    }

    /**
     * @param PasswordRevision $revision
     */
    protected function sendBadPasswordNotification(PasswordRevision $revision): void {
        try {
            $current = $this->revisionMapper->findCurrentRevisionByModel($revision->getModel());
            if($current->getUuid() === $revision->getUuid()) {
                $user = $revision->getUserId();
                if(!isset($this->badPasswords[ $user ])) {
                    $this->badPasswords[ $user ] = 1;
                } else {
                    $this->badPasswords[ $user ]++;
                }
            }
        } catch(Throwable $e) {
            $this->logger->logException($e);
        }
    }

    /**
     *
     */
    protected function notifyUsers(): void {
        foreach($this->badPasswords as $user => $count) {
            $this->notificationService->sendBadPasswordNotification($user, $count);
            $this->mailService->sendBadPasswordMail($user, $count);
        }
    }

    /**
     * @param $userId
     *
     * @return int
     */
    protected function getUserHashLength($userId): int {
        if(!isset($this->hashLengths[ $userId ])) {
            try {
                $this->hashLengths[ $userId ] = $this->userSettingsHelper->get('password.security.hash', $userId);
            } catch(Throwable $e) {
                $this->logger->logException($e);
                $this->hashLengths[ $userId ] = 40;
            }
        }

        return $this->hashLengths[ $userId ];
    }

    /**
     * @param PasswordRevision $revision
     *
     * @throws DecryptedDataException
     * @throws \OCP\DB\Exception
     */
    protected function checkHashLength(PasswordRevision $revision): void {
        $hashLength = $this->getUserHashLength($revision->getUserId());
        if(strlen($revision->getHash()) > $hashLength) {
            if($hashLength !== 0) {
                $revision->setHash(substr($revision->getHash(), 0, $hashLength));
            } else {
                $revision->setHash('');
            }
            $this->revisionMapper->update($revision);
        }
    }

    protected function registerUpdateAttempt() {
        $attempts = intval($this->config->getAppValue(self::CONFIG_UPDATE_ATTEMPTS, 0));
        $attempts++;
        if($attempts >= 3) {
            $this->sendUpdateFailureNotification('Too many failed attempts');
            throw new \Exception('Breached password database update: Too many failed attempts');
        }
        $this->config->setAppValue(self::CONFIG_UPDATE_ATTEMPTS, $attempts);
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

    protected function registerUpdateSuccess() {
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
        $this->config->deleteAppValue(self::CONFIG_UPDATE_ATTEMPTS);
    }
}