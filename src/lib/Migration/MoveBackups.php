<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Services\BackupService;
use OCA\Passwords\Services\ConfigurationService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class MoveBackups
 *
 * @package OCA\Passwords\Migration
 * @TODO remove in 2020.1
 */
class MoveBackups implements IRepairStep {

    /**
     * @var BackupService
     */
    protected $backupService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * MoveBackups constructor.
     *
     * @param BackupService        $backupService
     * @param ConfigurationService $config
     */
    public function __construct(BackupService $backupService, ConfigurationService $config) {
        $this->backupService = $backupService;
        $this->config        = $config;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName(): string {
        return 'Move automated backups';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        if($this->config->hasAppValue('backups/moved')) return;

        $oldFolder = $this->backupService->getBackupFolder();
        $newFolder = $this->backupService->getBackupFolder(BackupService::AUTO_BACKUPS);
        $files     = $oldFolder->getDirectoryListing();

        $output->info('Migrating Backups');
        $output->startProgress(count($files));
        foreach($files as $file) {
            $output->advance(1);
            if(preg_match('/^[\d]{4}([\d\-_]{3}){5}\.json(\.gz)?$/', $file->getName()) && !$newFolder->fileExists($file->getName())) {
                $newFile = $newFolder->newFile($file->getName());
                $newFile->putContent($file->getContent());
                $file->delete();
            }
        }
        $output->finishProgress();
        $this->backupService->removeOldBackups();

        $this->config->setAppValue('backups/moved', 'yes');
    }
}