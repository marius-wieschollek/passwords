<?php

namespace OCA\Passwords\Command;

use OCA\Passwords\Services\BackupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupRestoreCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupRestoreCommand extends Command {

    /**
     * @var BackupService
     */
    protected $backupService;

    /**
     * BackupListCommand constructor.
     *
     * @param BackupService $backupService
     */
    public function __construct(BackupService $backupService) {
        $this->backupService = $backupService;

        parent::__construct(null);
    }

    /**
     *
     */
    protected function configure() {
        $this->setName('passwords:backup:restore')
             ->setDescription('Restores a backup')
             ->addArgument('backup', InputArgument::REQUIRED, 'The name of the backup')
             ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Restore data only for this user')
             ->addOption('data', 'd', InputOption::VALUE_OPTIONAL, 'Restore only the given type of objects. Possible options are passwords, folders, tags, shares, relations or all', 'all')
             ->addOption('settings', 's', InputOption::VALUE_OPTIONAL, 'Restore system and user settings', false);
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $backup = $this->getBackup($input->getArgument('backup'));
    }

    /**
     * @param $name
     *
     * @return \OCP\Files\SimpleFS\ISimpleFile
     * @throws \OCP\Files\NotPermittedException
     * @throws \Exception
     */
    protected function getBackup($name) {
        $backups = $this->backupService->getBackups();
        foreach($backups as $backup) {
            if(substr($backup->getName(), 0, strpos($backup->getName(), '.json')) === $name) return $backup;
        }

        throw new \Exception("Could not find backup '{$name}'");
    }

}