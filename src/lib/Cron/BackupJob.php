<?php

namespace OCA\Passwords\Cron;

use OCA\Passwords\Services\BackupService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class BackupJob
 *
 * @package OCA\Passwords\Cron
 */
class BackupJob extends AbstractCronJob {

    /**
     * @var BackupService
     */
    protected $backupService;
    /**
     * @var ConfigurationService
     */
    private $config;

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
        $this->config = $config;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
        $time = time() - intval($this->config->getAppValue('backup/timestamp', 0));
        $interval = intval($this->config->getAppValue('backup/interval', 86400));
        if($time < $interval) return;

        $this->config->setAppValue('backup/timestamp', time());
        $file = $this->backupService->createBackup();
        $this->logger->info(['Created Backup %s with %s', $file->getName(), \OC_Helper::humanFileSize($file->getSize())]);
    }
}