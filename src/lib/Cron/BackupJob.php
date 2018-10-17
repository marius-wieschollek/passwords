<?php

namespace OCA\Passwords\Cron;

use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class BackupJob
 *
 * @package OCA\Passwords\Cron
 */
class BackupJob extends AbstractCronJob {

    /**
     * @var \OCA\Passwords\Services\BackupService
     */
    protected $backupService;

    /**
     * BackupJob constructor.
     *
     * @param LoggingService                        $logger
     * @param EnvironmentService                    $environment
     * @param \OCA\Passwords\Services\BackupService $backupService
     */
    public function __construct(LoggingService $logger, EnvironmentService $environment, \OCA\Passwords\Services\BackupService $backupService) {
        parent::__construct($logger, $environment);
        $this->backupService = $backupService;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
        $file = $this->backupService->createBackup();
        $this->logger->info(['Created Backup %s with %s', $file->getName(), \OC_Helper::humanFileSize($file->getSize())]);
    }
}