<?php

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Backup\CreateBackupHelper;
use OCA\Passwords\Helper\Backup\RestoreBackupHelper;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;

/**
 * Class BackupService
 *
 * @package OCA\Passwords\Services
 */
class BackupService {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IAppData
     */
    protected $appData;

    /**
     * @var CreateBackupHelper
     */
    protected $createBackupHelper;

    /**
     * @var RestoreBackupHelper
     */
    protected $restoreBackupHelper;

    /**
     * BackupService constructor.
     *
     * @param CreateBackupHelper $createBackupHelper
     */
    public function __construct(IAppData $appData, CreateBackupHelper $createBackupHelper, RestoreBackupHelper $restoreBackupHelper, ConfigurationService $config) {
        $this->appData             = $appData;
        $this->createBackupHelper  = $createBackupHelper;
        $this->restoreBackupHelper = $restoreBackupHelper;
        $this->config              = $config;
    }

    /**
     * @return \OCP\Files\SimpleFS\ISimpleFile
     * @throws \OCP\Files\NotPermittedException
     * @throws \Exception
     */
    public function createBackup(): ISimpleFile {
        $name = date('Y-m-d_H-i-s').'.json';
        $data = json_encode($this->createBackupHelper->getData());
        if(extension_loaded('zlib')) {
            $name .= '.gz';
            $data = gzencode($data);
        }

        $folder = $this->getBackupFolder();
        $file   = $folder->newFile($name);
        $file->putContent($data);

        $this->removeOldBackups();

        return $file;
    }

    /**
     * @return \OCP\Files\SimpleFS\ISimpleFile[]
     * @throws \OCP\Files\NotPermittedException
     */
    public function getBackups(): array {
        $folder = $this->getBackupFolder();

        return $folder->getDirectoryListing();
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return bool
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
     */
    public function restoreBackup(string $name, $options = []): bool {
        $folder = $this->getBackupFolder();
        if(!$folder->fileExists($name)) return false;

        $file = $folder->getFile($name);
        $data = $file->getContent();
        if(substr($file->getName(), -2) === 'gz') {
            if(!extension_loaded('zlib')) throw new \Exception('PHP extension zlib is required to read compressed backup.');

            $data = gzdecode($data);
        }
        $backup = json_decode($data, true);

        return $this->restoreBackupHelper->restore($backup, $options);
    }

    /**
     * @return \OCP\Files\SimpleFS\ISimpleFolder
     * @throws \OCP\Files\NotPermittedException
     */
    protected function getBackupFolder(): ISimpleFolder {
        try {
            return $this->appData->getFolder('backups');
        } catch(\OCP\Files\NotFoundException $e) {
            return $this->appData->newFolder('backups');
        }
    }

    /**
     * @throws \OCP\Files\NotPermittedException
     */
    protected function removeOldBackups(): void {
        $maxBackups = $this->config->getAppValue('backup/files/maximum', 14);
        if($maxBackups === 0) return;

        $backups = $this->getBackups();
        if(count($backups) <= $maxBackups) return;

        $delete = count($backups) - $maxBackups;
        for($i = 0; $i < $delete; $i++) {
            $backups[ $i ]->delete();
        }
    }
}