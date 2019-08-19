<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use OCA\Passwords\Services\BackupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
    protected function configure(): void {
        $this->setName('passwords:backup:list')
             ->setDescription('Print a list of the available backups');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \OCP\Files\NotPermittedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void {
        $backups = $this->backupService->getBackups();

        if(empty($backups)) $output->writeln('No backups found');

        $output->writeln('The following backups are available:');
        foreach($backups as $backup) {
            $info = $this->backupService->getBackupInfo($backup);

            $output->writeln(sprintf('   %-20s  %s %s', $info['label'], $info['size'], $info['format']));
        }
    }
}