<?php

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Backup\CreateBackupHelper;
use OCA\Passwords\Helper\Backup\RestoreBackupHelper;
use OCP\Files\IAppData;

/**
 * Class BackupService
 *
 * @package OCA\Passwords\Services
 */
class BackupService {

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
    public function __construct(IAppData $appData, CreateBackupHelper $createBackupHelper, RestoreBackupHelper $restoreBackupHelper) {
        $this->appData             = $appData;
        $this->createBackupHelper  = $createBackupHelper;
        $this->restoreBackupHelper = $restoreBackupHelper;
    }

    /**
     * @return \OCP\Files\SimpleFS\ISimpleFile
     * @throws \OCP\Files\NotPermittedException
     * @throws \Exception
     */
    public function createBackup() {
        $name = date('Y-m-d_H-i-s').'.json';
        $data = json_encode($this->createBackupHelper->getData());
        if(extension_loaded('zlib')) {
            $name .= '.gz';
            $data = gzencode($data);
        }

        $folder = $this->getBackupFolder();
        $file   = $folder->newFile($name);
        $file->putContent($data);

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
            $data = gzdecode($data);
        }
        $backup = json_decode($data, true);

        return $this->restoreBackupHelper->restore($backup, $options);
    }

    /**
     * @return \OCP\Files\SimpleFS\ISimpleFolder
     * @throws \OCP\Files\NotPermittedException
     */
    protected function getBackupFolder() {
        try {
            return $this->appData->getFolder('backups');
        } catch(\OCP\Files\NotFoundException $e) {
            return $this->appData->newFolder('backups');
        }
    }
}