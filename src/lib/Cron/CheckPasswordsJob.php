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
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\MailService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\PasswordSecurityCheckService;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Class CheckPasswordsJob
 *
 * @package OCA\Passwords\Cron
 */
class CheckPasswordsJob extends AbstractTimedJob {

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
     * @param ITimeFactory                 $time
     * @param LoggingService               $logger
     * @param MailService                  $mailService
     * @param ConfigurationService         $config
     * @param EnvironmentService           $environment
     * @param UserSettingsHelper           $userSettingsHelper
     * @param PasswordRevisionMapper       $revisionMapper
     * @param NotificationService          $notificationService
     * @param PasswordSecurityCheckService $securityCheckService
     */
    public function __construct(
        ITimeFactory $time,
        LoggingService                   $logger,
        protected MailService            $mailService,
        ConfigurationService             $config,
        EnvironmentService               $environment,
        protected UserSettingsHelper     $userSettingsHelper,
        protected PasswordRevisionMapper $revisionMapper,
        protected NotificationService    $notificationService,
        protected PasswordSecurityCheckService $securityCheckService
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
        $this->securityCheckService->updateDb();
        $this->checkAllPasswordRevisions();
    }

    /**
     * @throws Exception
     */
    protected function checkAllPasswordRevisions(): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionMapper->findAll();

        $badRevisionCounter = 0;
        foreach($revisions as $revision) {
            $this->checkHashLength($revision);

            if($revision->getStatus() === PasswordSecurityCheckService::LEVEL_BAD || $revision->getStatus() === PasswordSecurityCheckService::LEVEL_UNKNOWN) continue;

            $oldStatusCode = $revision->getStatusCode();
            [$statusLevel, $statusCode] = $this->securityCheckService->getRevisionSecurityLevel($revision);

            if($oldStatusCode !== $statusCode) {
                $revision->setStatus($statusLevel);
                $revision->setStatusCode($statusCode);
                $revision->setUpdated(time());
                $this->revisionMapper->update($revision);

                if($statusLevel === PasswordSecurityCheckService::LEVEL_BAD) {
                    $this->sendBadPasswordNotification($revision);
                    $badRevisionCounter++;
                }
            }
        }

        $this->notifyUsers();
        $this->logger->debugOrInfo(['Checked %s passwords. %s new bad revisions found', count($revisions), $badRevisionCounter], $badRevisionCounter);
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
}