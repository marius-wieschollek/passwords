<?php

namespace OCA\Passwords\Cron;

use Exception;
use OCA\Passwords\Services\BackupService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCP\Util;

/**
 * Class BackupJob
 *
 * @package OCA\Passwords\Cron
 */
class BackupJob extends AbstractTimedJob {

    /**
     * @var BackupService
     */
    protected $backupService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * BackupJob constructor.
     *
     * @param LoggingService       $logger
     * @param EnvironmentService   $environment
     * @param BackupService        $backupService
     * @param ConfigurationService $config
     */
    public function __construct(LoggingService $logger, EnvironmentService $environment, BackupService $backupService, ConfigurationService $config) {
        parent::__construct($logger, $environment);
        $this->backupService = $backupService;
        $this->config        = $config;

        $interval = (int) $this->config->getAppValue('backup/interval', 86400);
        $this->setInterval($interval);
    }

    /**
     * @param $argument
     *
     * @throws Exception
     */
    protected function runJob($argument): void {
        $file = $this->backupService->createBackup(null, BackupService::AUTO_BACKUPS);
        $this->logger->info(['Created Backup %s with %s', $file->getName(), Util::humanFileSize($file->getSize())]);
        $this->backupService->removeOldBackups();
    }
}