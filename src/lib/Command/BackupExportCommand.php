<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Services\BackupService;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupExportCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupExportCommand extends Command {

    /**
     * @var BackupService
     */
    protected BackupService $backupService;

    /**
     * BackupExportCommand constructor.
     *
     * @param BackupService $backupService
     */
    public function __construct(BackupService $backupService) {
        $this->backupService = $backupService;

        parent::__construct();
    }

    /**
     *
     */
    protected function configure(): void {
        $this->setName('passwords:backup:export')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the backup')
             ->addArgument('file', InputArgument::OPTIONAL, 'The path of the export file')
             ->setDescription('Export a backup to a file');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws NotPermittedException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $backup     = $this->getBackup($input->getArgument('name'));
        $exportPath = $this->getFilePath($input, $backup);

        file_put_contents($exportPath, $backup->getContent());
        $output->writeln('Exported backup to '.$exportPath);

        return 0;
    }

    /**
     * @param $name
     *
     * @return ISimpleFile
     * @throws NotPermittedException
     * @throws Exception
     */
    protected function getBackup($name): ISimpleFile {
        $backups = $this->backupService->getBackups();

        if(isset($backups[ $name ])) return $backups[ $name ];

        throw new Exception("Could not find backup '{$name}'");
    }

    /**
     * @param InputInterface $input
     * @param ISimpleFile    $backup
     *
     * @return string
     * @throws Exception
     */
    protected function getFilePath(InputInterface $input, ISimpleFile $backup): string {
        if(!$input->hasArgument('file')) return getcwd().DIRECTORY_SEPARATOR.$backup->getName();

        $exportFolder = $input->getArgument('file');
        if($exportFolder[0] !== '/') $exportFolder = getcwd().DIRECTORY_SEPARATOR.$exportFolder;

        $exportFile = $backup->getName();
        $fileEnding = substr($exportFile, strrpos($exportFile, '.json'));
        if(substr($exportFolder, -strlen($fileEnding)) === $fileEnding) {
            $exportFile   = basename($exportFolder);
            $exportFolder = dirname($exportFolder);
        }

        if(!is_dir($exportFolder)) {
            if(is_file($exportFolder)) throw new Exception('Invalid export file path');
            mkdir($exportFolder, 0777, true);
        }

        $exportPath = $exportFolder.DIRECTORY_SEPARATOR.$exportFile;
        if(file_exists($exportPath) && !is_file($exportPath)) throw new Exception('Invalid export file path');

        return $this->normalizePath($exportPath);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath(string $path): string {
        $path      = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts     = explode(DIRECTORY_SEPARATOR, $path);
        $absolutes = [];
        foreach($parts as $part) {
            if('.' === $part || empty($part)) continue;
            if('..' === $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}