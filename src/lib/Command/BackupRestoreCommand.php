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
             ->addOption('no-data', null, InputOption::VALUE_NONE, 'Do not restore user data and encryption keys')
             ->addOption('no-user-settings', null, InputOption::VALUE_NONE, 'Do not restore user settings')
             ->addOption('no-app-settings', null, InputOption::VALUE_NONE, 'Do not restore application settings');
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
        $options = $this->getOptions($input);

        $backup  = $this->getBackup($input->getArgument('backup'));
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

    /**
     * @param InputInterface $input
     *
     * @return array
     */
    protected function getOptions(InputInterface $input): array {
        return [
            'user'     => $input->getOption('user'),
            'data'     => !$input->getOption('no-data'),
            'settings' => [
                'user'        => !$input->getOption('no-user-settings'),
                'client'      => !$input->getOption('no-user-settings'),
                'application' => !$input->getOption('no-app-settings') && $input->getOption('user') === null
            ]
        ];
    }

}