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
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\BackgroundJobService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Class ProcessDeletedUsers
 *
 * @package OCA\Passwords\Cron
 */
class ProcessDeletedUser extends AbstractQueuedJob {

    /**
     * ProcessDeletedUsers constructor.
     *
     * @param ITimeFactory         $time
     * @param LoggingService       $logger
     * @param EnvironmentService   $environment
     * @param DeleteUserDataHelper $deleteUserDataHelper
     * @param BackgroundJobService $backgroundJobService
     */
    public function __construct(
        ITimeFactory $time,
        LoggingService $logger,
        EnvironmentService $environment,
        protected DeleteUserDataHelper $deleteUserDataHelper,
        BackgroundJobService $backgroundJobService
    ) {
        parent::__construct($time, $logger, $environment, $backgroundJobService);
    }

    /**
     * @param $userId
     *
     * @throws Exception
     */
    protected function runJob($userId): void {
        $this->deleteUserDataHelper->deleteUserData($userId);
        $this->logger->info(['Database of %s deleted due to user deletion', $userId]);
    }
}