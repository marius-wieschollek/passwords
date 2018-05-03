<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OC\BackgroundJob\TimedJob;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\LoggingService;
use OCP\BackgroundJob;

/**
 * Class ProcessDeletedUsers
 *
 * @package OCA\Passwords\Cron
 */
class ProcessDeletedUsers extends TimedJob {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var DeleteUserDataHelper
     */
    protected $deleteUserDataHelper;

    /**
     * ProcessDeletedUsers constructor.
     *
     * @param LoggingService       $logger
     * @param ConfigurationService $config
     * @param DeleteUserDataHelper $deleteUserDataHelper
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        DeleteUserDataHelper $deleteUserDataHelper
    ) {
        // Run always
        $this->setInterval(1);

        $this->logger               = $logger;
        $this->config               = $config;
        $this->deleteUserDataHelper = $deleteUserDataHelper;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function run($argument): void {
        if(BackgroundJob::getExecutionType() === 'ajax') {
            $this->logger->error('Ajax cron jobs are not supported');

            return;
        }

        $usersToDelete   = json_decode($this->config->getAppValue('deleted_users', '{}'), true);
        $usersNotDeleted = [];
        $deleted         = 0;
        foreach($usersToDelete as $userId) {
            if($this->deleteUserData($userId)) {
                $deleted++;
            } else {
                $usersNotDeleted[] = $userId;
            };
        }
        $this->logger->info(['Deleted %s user(s)', $deleted]);
        $this->config->setAppValue('deleted_users', json_encode($usersNotDeleted));
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    protected function deleteUserData(string $userId): bool {
        try {
            $this->deleteUserDataHelper->deleteUserData($userId);

            return true;
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return false;
    }
}