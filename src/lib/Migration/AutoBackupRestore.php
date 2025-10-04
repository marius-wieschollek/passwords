<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\BackupService;
use OCA\Passwords\Services\NotificationService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class AutoBackupRestore
 *
 * @package OCA\Passwords\Migration
 */
class AutoBackupRestore implements IRepairStep {

    /**
     * AutoBackupRestore constructor.
     *
     * @param PasswordRevisionMapper $passwordRevisionMapper
     * @param FolderRevisionMapper   $folderRevisionMapper
     * @param TagRevisionMapper      $tagRevisionMapper
     * @param NotificationService    $notifications
     * @param AdminUserHelper        $adminHelper
     * @param BackupService          $backupService
     * @param IAppConfig             $config
     */
    public function __construct(
        protected PasswordRevisionMapper $passwordRevisionMapper,
        protected FolderRevisionMapper $folderRevisionMapper,
        protected TagRevisionMapper $tagRevisionMapper,
        protected NotificationService $notifications,
        protected AdminUserHelper $adminHelper,
        protected BackupService $backupService,
        protected IAppConfig $config
    ) {
    }

    /**
     * @return string
     */
    public function getName() {
        return 'Automatic Backup Restore';
    }

    /**
     * @param IOutput $output
     *
     * @throws \OCP\Files\NotPermittedException
     */
    public function run(IOutput $output) {
        $enabled = $this->config->getAppValueBool('backup/update/autorestore', true);
        if(!$enabled || !empty($this->passwordRevisionMapper->findAll()) || !empty($this->folderRevisionMapper->findAll()) || !empty($this->tagRevisionMapper->findAll())) {
            $this->config->setAppValueInt('auto-backup/status', 0);
            return;
        }

        $backups = $this->backupService->getBackups();
        if(empty($backups)) {
            $this->config->setAppValueInt('auto-backup/status', 1);
            return;
        }
        $backups = array_reverse($backups, true);
        foreach($backups as $name => $backup) {
            $info = $this->backupService->getBackupInfo($backup, true);
            if(!isset($info['entities'])) {
                continue;
            }

            if($info['entities']['passwords'] > 0 || $info['entities']['folders'] > 0 || $info['entities']['tags'] > 0) {
                try {
                    $this->backupService->createBackup('AUTO_'.date('y-m-d_H-i'), BackupService::AUTO_BACKUPS);

                    $options = [
                        'user'     => null,
                        'data'     => true,
                        'settings' => ['user' => true, 'client' => true, 'application' => true]
                    ];
                    $this->backupService->restoreBackup($name, $options);
                    $this->sendNotification('sendBackupRestoredNotification', $name);
                    $this->config->setAppValueInt('auto-backup/status', 2);
                    break;
                } catch(\Throwable $e) {
                    $this->sendNotification('sendBackupFailedNotification', $name);
                    $this->config->setAppValueInt('auto-backup/status', 3);
                }
            }
        }
    }

    /**
     * @param string $name
     * @param string $backup
     */
    protected function sendNotification(string $name, string $backup): void {
        foreach($this->adminHelper->getAdmins() as $admin) {
            $this->notifications->{$name}($admin->getUID(), $backup);
        }
    }
}