<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class ProcessDeletedUsers
 *
 * @package OCA\Passwords\Cron
 */
class ProcessDeletedUsers extends AbstractCronJob {

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
     * @param EnvironmentService   $environment
     * @param DeleteUserDataHelper $deleteUserDataHelper
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        EnvironmentService $environment,
        DeleteUserDataHelper $deleteUserDataHelper
    ) {
        $this->config               = $config;
        $this->deleteUserDataHelper = $deleteUserDataHelper;
        parent::__construct($logger, $environment);
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
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
        $this->logger->debugOrInfo(['Deleted %s user(s)', $deleted], $deleted);
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