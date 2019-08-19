<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Helper\Backup\BackupMigrationHelper;
use OCA\Passwords\Services\BackupService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupImportCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupImportCommand extends Command {

    /**
     * @var BackupService
     */
    protected $backupService;

    /**
     * @var BackupMigrationHelper
     */
    protected $migrationHelper;

    /**
     * BackupListCommand constructor.
     *
     * @param BackupService $backupService
     */
    public function __construct(BackupService $backupService, BackupMigrationHelper $migrationHelper) {
        $this->backupService = $backupService;
        $this->migrationHelper = $migrationHelper;

        parent::__construct();
    }

    /**
     *
     */
    protected function configure(): void {
        $this->setName('passwords:backup:import')
             ->addArgument('file', InputArgument::REQUIRED, 'The name of the backup')
             ->setDescription('Import a backup from a file');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws NotPermittedException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void {
        $file = realpath($input->getArgument('file'));

        list($importFile, $isCompressed) = $this->checkIfFileCanBeRead($file);

        $json = $this->readFile($file, $isCompressed);
        $json = $this->validateData($json);
        list($name, $data) = $this->encodeData($importFile, $json);
        $backup = $this->createBackup($name, $data);
        $info   = $this->backupService->getBackupInfo($backup);

        $output->writeln(sprintf('Imported backup: %s, %s %s', $info['label'], $info['size'], $info['format']));
    }

    /**
     * @param string $file
     *
     * @return array
     * @throws NotFoundException
     */
    protected function checkIfFileCanBeRead(string $file): array {
        if(!$file || !is_file($file) || !is_readable($file)) throw new NotFoundException();
        $importFile = basename($file);
        if(!preg_match('/[\w+\-\.](\.json(\.gz)?)$/', $importFile, $matches)) {
            throw new Exception('Invalid file type');
        }

        $isCompressed = isset($matches[2]);
        if($isCompressed && !extension_loaded('zlib')) {
            throw new Exception('PHP extension zlib is required to import compressed backups.');
        }

        return [$importFile, $isCompressed];
    }

    /**
     * @param $file
     * @param $isCompressed
     *
     * @return array
     */
    protected function readFile(string $file, bool $isCompressed): array {
        $data = file_get_contents($file);
        if($isCompressed) {
            $data = gzdecode($data);
        }

        return json_decode($data, true);
    }

    /**
     * @param array $json
     *
     * @return array
     * @throws Exception
     */
    protected function validateData(array $json): array {
        if(!isset($json['version']) || $json['version'] < 100 || !isset($json['keys'])) {
            throw new Exception('The file does not contain a valid server backup');
        }

        return $this->migrationHelper->convert($json);
    }

    /**
     * @param       $importFile
     * @param array $json
     *
     * @return array
     */
    protected function encodeData($importFile, array $json): array {
        $label = substr($importFile, 0, strrpos($importFile, '.json'));
        if(strlen($label) > 20) $label = substr($label, 0, 20);

        $name = $label.'.json';
        $data = json_encode($json);
        if(extension_loaded('zlib')) {
            $name .= '.gz';
            $data = gzencode($data);
        }

        return [$name, $data];
    }

    /**
     * @param $name
     * @param $data
     *
     * @return ISimpleFile
     * @throws NotFoundException
     * @throws NotPermittedException
     */
    protected function createBackup($name, $data): ISimpleFile {
        $folder = $this->backupService->getBackupFolder();

        if($folder->fileExists($name)) {
            $oldFile = $folder->getFile($name);
            $oldFile->delete();
        }

        $file = $folder->newFile($name);
        $file->putContent($data);

        return $file;
    }
}