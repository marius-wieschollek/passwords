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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupRestoreCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupRestoreCommand extends AbstractInteractiveCommand {

    /**
     * @var BackupService
     */
    protected BackupService $backupService;

    /**
     * BackupRestoreCommand constructor.
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
    protected function configure() {
        $this->setName('passwords:backup:restore')
             ->setDescription('Restores a backup')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the backup')
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
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        parent::execute($input, $output);
        $options = $this->getOptions($input);
        $backup  = $this->getBackup($input->getArgument('name'));

        $this->printRestoringInformation($output, $backup, $options);
        if(!$options['data'] && !$options['settings']['application'] && !$options['settings']['user'] && !$options['settings']['client']) {
            $output->writeln(' - nothing');

            return 1;
        }

        if($options['data']) {
            $output->writeln('');
            $output->writeln('Restoring user data means that the current user data will be wiped.');
        }

        if(!$this->confirmRestoring($input, $output, $backup)) return 2;

        $output->write('Restoring backup ...');
        $this->backupService->restoreBackup($backup, $options);
        $output->write(' done');
        $output->writeln('');

        return 0;
    }

    /**
     * @param $name
     *
     * @return string
     * @throws NotPermittedException
     * @throws Exception
     */
    protected function getBackup($name): string {
        $backups = $this->backupService->getBackups();

        if(isset($backups[ $name ])) return $name;

        throw new Exception("Could not find backup '{$name}'");
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $backup
     *
     * @return bool
     */
    protected function confirmRestoring(InputInterface $input, OutputInterface $output, string $backup): bool {
        return $this->requestConfirmation($input, $output, 'The backup "'.$backup.'" will now be restored');
    }

    /**
     * @param OutputInterface $output
     * @param string          $backup
     * @param array           $options
     */
    protected function printRestoringInformation(OutputInterface $output, string $backup, array $options): void {
        $output->writeln('This backup file will be used: '.$backup);
        $output->writeln('');
        $output->writeln('The following will be restored:');
        if($options['user']) {
            $output->writeln(' - Only data for '.escapeshellarg($options['user']));
        }
        if($options['data']) {
            $output->writeln(' - The Nextcloud server secret');
            $output->writeln(' - Server and user encryption keys');
            $output->writeln(' - User passwords, folder, tags and shares');
        }

        if($options['settings']['application']) $output->writeln(' - Application settings');
        if($options['settings']['user']) $output->writeln(' - User settings');
        if($options['settings']['client']) $output->writeln(' - Third party client settings');
    }

}