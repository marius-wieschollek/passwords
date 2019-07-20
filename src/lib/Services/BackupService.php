<?php

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Backup\CreateBackupHelper;
use OCA\Passwords\Helper\Backup\RestoreBackupHelper;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Util;

/**
 * Class BackupService
 *
 * @package OCA\Passwords\Services
 */
class BackupService {
    const AUTO_BACKUPS = 'autoBackups';
    const BACKUPS      = 'backups';

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
     * @param string|null $name
     *
     * @param string|null $folder
     *
     * @return \OCP\Files\SimpleFS\ISimpleFile
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
     * @throws \Exception
     */
    public function createBackup(?string $name = null, string $folder = self::BACKUPS): ISimpleFile {
        if(empty($name)) {
            $name = date('Y-m-d_H-i-s');
        } else if(strlen($name) > 20) {
            $name = substr($name, 0, 20);
        }

        $backups = $this->getBackups();
        if(isset($backups[ $name ])) $backups[ $name ]->delete();

        $name .= '.json';
        $data = json_encode($this->createBackupHelper->getData());
        if(extension_loaded('zlib')) {
            $name .= '.gz';
            $data = gzencode($data);
        }

        $folder = $this->getBackupFolder($folder);
        $file   = $folder->newFile($name);
        $file->putContent($data);

        return $file;
    }

    /**
     * @param string|null $location
     *
     * @return \OCP\Files\SimpleFS\ISimpleFile[]
     * @throws \OCP\Files\NotPermittedException
     */
    public function getBackups(?string $location = null): array {
        $folders = [self::BACKUPS, self::AUTO_BACKUPS];
        if(in_array($location, $folders)) $folders = [$location];

        $backups = [];
        foreach($folders as $folder) {
            $files = $this->getBackupFolder($folder)->getDirectoryListing();

            foreach($files as $file) {
                $name = $file->getName();
                $name = substr($name, 0, strrpos($name, '.json'));

                $backups[ $name ] = $file;
            }
        }
        ksort($backups);

        return $backups;
    }

    /**
     * @param ISimpleFile $backup
     *
     * @return array
     */
    public function getBackupInfo(ISimpleFile $backup): array {
        $name = $backup->getName();
        preg_match('/^([\w\-\.]+)(\.json(\.gz)?)$/', $name, $matches);

        return [
            'label'  => $matches[1],
            'name'   => $name,
            'size'   => Util::humanFileSize($backup->getSize()),
            'format' => isset($matches[3]) ? 'compressed':'json'
        ];
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return bool
     * @throws \OCP\Files\NotFoundException
     * @throws \OCP\Files\NotPermittedException
     * @throws \Exception
     */
    public function restoreBackup(string $name, $options = []): bool {
        $backups = $this->getBackups();
        if(!isset($backups[ $name ])) return false;

        $file = $backups[ $name ];
        $data = $file->getContent();
        if(substr($file->getName(), -2) === 'gz') {
            if(!extension_loaded('zlib')) throw new \Exception('PHP extension zlib is required to read compressed backups.');

            $data = gzdecode($data);
        }
        $backup = json_decode($data, true);

        return $this->restoreBackupHelper->restore($backup, $options);
    }

    /**
     * @throws \OCP\Files\NotPermittedException
     */
    public function removeOldBackups(): void {
        $maxBackups = $this->config->getAppValue('backup/files/maximum', 14);
        if($maxBackups === 0) return;

        $backups = array_values($this->getBackups(self::AUTO_BACKUPS));
        if(count($backups) <= $maxBackups) return;

        $delete = count($backups) - $maxBackups;
        for($i = 0; $i < $delete; $i++) {
            $backups[ $i ]->delete();
        }
    }

    /**
     * @param string $name
     *
     * @return \OCP\Files\SimpleFS\ISimpleFolder
     * @throws \OCP\Files\NotPermittedException
     */
    public function getBackupFolder(string $name = self::BACKUPS): ISimpleFolder {
        try {
            return $this->appData->getFolder($name);
        } catch(\OCP\Files\NotFoundException $e) {
            return $this->appData->newFolder($name);
        }
    }
}