<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\MailService;
use OCA\Passwords\Services\NotificationService;

/**
 * Class CheckPasswordsJob
 *
 * @package OCA\Passwords\Cron
 */
class CheckPasswordsJob extends AbstractTimedJob {

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var HelperService
     */
    protected $helperService;

    /**
     * @var PasswordRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * @var array
     */
    protected $badPasswords = [];

    /**
     * @var float|int
     */
    protected $interval = 24 * 60 * 60;

    /**
     * CheckPasswordsJob constructor.
     *
     * @param LoggingService         $logger
     * @param MailService            $mailService
     * @param HelperService          $helperService
     * @param EnvironmentService     $environment
     * @param PasswordRevisionMapper $revisionMapper
     * @param NotificationService    $notificationService
     */
    public function __construct(
        LoggingService $logger,
        MailService $mailService,
        HelperService $helperService,
        EnvironmentService $environment,
        PasswordRevisionMapper $revisionMapper,
        NotificationService $notificationService
    ) {
        $this->helperService       = $helperService;
        $this->revisionMapper      = $revisionMapper;
        $this->mailService         = $mailService;
        $this->notificationService = $notificationService;
        parent::__construct($logger, $environment);
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
        $securityHelper = $this->helperService->getSecurityHelper();

        if($securityHelper->dbUpdateRequired()) $securityHelper->updateDb();
        $this->checkRevisionStatus($securityHelper);
    }

    /**
     * @param $securityHelper
     *
     * @throws \Exception
     */
    protected function checkRevisionStatus(AbstractSecurityCheckHelper $securityHelper): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionMapper->findAllWithGoodStatus();

        $badRevisionCounter = 0;
        foreach($revisions as $revision) {
            $oldStatusCode = $revision->getStatusCode();
            list($statusLevel, $statusCode) = $securityHelper->getRevisionSecurityLevel($revision);

            if($oldStatusCode != $statusCode) {
                $revision->setStatus($statusLevel);
                $revision->setStatusCode($statusCode);
                $revision->setUpdated(time());
                $this->revisionMapper->update($revision);

                if($statusLevel == AbstractSecurityCheckHelper::LEVEL_BAD) {
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
        } catch(\Throwable $e) {
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
}