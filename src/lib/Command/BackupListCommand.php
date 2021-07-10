<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use OCA\Passwords\Services\BackupService;
use OCP\Files\NotPermittedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupListCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupListCommand extends Command {

    /**
     * @var BackupService
     */
    protected BackupService $backupService;

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
    protected function configure(): void {
        $this->setName('passwords:backup:list')
             ->addOption('details', 'd', InputOption::VALUE_NONE, 'Inspect backup files and list contents')
             ->setDescription('Print a list of the available backups');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws NotPermittedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $backups = $this->backupService->getBackups();

        if(empty($backups)) {
            $output->writeln('No backups found');

            return 0;
        }

        $details = (bool) $input->getOption('details');
        $output->writeln('The following backups are available:');
        foreach($backups as $backup) {
            $info = $this->backupService->getBackupInfo($backup, $details);

            $output->writeln(sprintf('  %-20s  %s %s', $info['label'], $info['size'], $info['format']));

            if($details) {
                $space = str_repeat(' ', 4);
                if(isset($info['error'])) {
                    $output->writeln(sprintf('%sError %s', $space, $info['error']));
                }
                if(isset($info['version'])) {
                    $output->writeln(sprintf('%sVersion %s', $space, $info['version']));
                }
                if(isset($info['users'])) {
                    $output->writeln(sprintf('%s%-5s users', $space, $info['users']));
                }
                if(isset($info['entities'])) {
                    foreach($info['entities'] as $key => $value) {
                        $output->writeln(sprintf('%s%-5s %s', $space, $value, $key));
                    }
                }
            }
        }

        return 0;
    }
}