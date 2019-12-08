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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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

        parent::__construct();
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
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $options = $this->getOptions($input);
        $backup  = $this->getBackup($input->getArgument('backup'));

        $this->printRestoringInformation($output, $backup, $options);
        if(!$options['data'] && !$options['settings']['application'] && !$options['settings']['user'] && !$options['settings']['client']) {
            $output->writeln(' - nothing');

            return;
        }

        if($options['data']) {
            $output->writeln('');
            $output->writeln('Restoring user data means that the current user data will be wiped.');
        }

        if(!$this->confirmRestoring($input, $output)) return;

        $output->writeln('');
        $output->write('Restoring backup ...');
        $this->backupService->restoreBackup($backup, $options);
        $output->write(' done');
        $output->writeln('');
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
     *
     * @return bool
     */
    protected function confirmRestoring(InputInterface $input, OutputInterface $output): bool {
        if(!$input->getOption('no-interaction')) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');

            $question = new Question('Type "yes" to confirm that you want to restore the backup: ');
            $yes      = $helper->ask($input, $output, $question);

            if($yes !== 'yes') {
                $output->writeln('aborting');

                return false;
            }
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param                 $backup
     * @param                 $options
     */
    protected function printRestoringInformation(OutputInterface $output, string $backup, array $options): void {
        $output->writeln('This backup file will be used: '.$backup);
        $output->writeln('');
        $output->writeln('The backup will restore the following:');
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