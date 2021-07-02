<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\MailService;
use OCA\Passwords\Services\NotificationService;
use Throwable;

/**
 * Class CheckPasswordsJob
 *
 * @package OCA\Passwords\Cron
 */
class CheckPasswordsJob extends AbstractTimedJob {

    /**
     * @var MailService
     */
    protected MailService $mailService;

    /**
     * @var HelperService
     */
    protected HelperService $helperService;

    /**
     * @var PasswordRevisionMapper
     */
    protected PasswordRevisionMapper $revisionMapper;

    /**
     * @var NotificationService
     */
    protected NotificationService $notificationService;

    /**
     * @var UserSettingsHelper
     */
    protected UserSettingsHelper $userSettingsHelper;

    /**
     * @var array
     */
    protected array $hashLengths = [];

    /**
     * @var array
     */
    protected array $badPasswords = [];

    /**
     * @var float|int
     */
    protected $interval = 24 * 60 * 60;

    /**
     * CheckPasswordsJob constructor.
     *
     * @param LoggingService         $logger
     * @param MailService            $mailService
     * @param ConfigurationService   $config
     * @param HelperService          $helperService
     * @param EnvironmentService     $environment
     * @param UserSettingsHelper     $userSettingsHelper
     * @param PasswordRevisionMapper $revisionMapper
     * @param NotificationService    $notificationService
     */
    public function __construct(
        LoggingService $logger,
        MailService $mailService,
        ConfigurationService $config,
        HelperService $helperService,
        EnvironmentService $environment,
        UserSettingsHelper $userSettingsHelper,
        PasswordRevisionMapper $revisionMapper,
        NotificationService $notificationService
    ) {
        $this->mailService         = $mailService;
        $this->helperService       = $helperService;
        $this->revisionMapper      = $revisionMapper;
        $this->userSettingsHelper  = $userSettingsHelper;
        $this->notificationService = $notificationService;
        parent::__construct($logger, $config, $environment);
    }

    /**
     * @param $argument
     *
     * @throws Exception
     */
    protected function runJob($argument): void {
        $securityHelper = $this->helperService->getSecurityHelper();

        if($securityHelper->dbUpdateRequired()) $securityHelper->updateDb();
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

            if($revision->getStatus() === 2) continue;

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
}