<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use OCA\Passwords\Services\BackupService;
use OCP\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupCreateCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupCreateCommand extends Command {

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
        $this->setName('passwords:backup:create')
             ->addArgument('name', InputArgument::OPTIONAL, 'The name of the backup')
             ->setDescription('Create a new backup of the password database');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \OCP\Files\NotPermittedException
     * @throws \OCP\Files\NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void {
        $argName = null;
        if($input->hasArgument('name')) {
            $argName = preg_replace('/[^\w\-\.]/', '', $input->getArgument('name'));
            if(empty($argName)) $argName = null;
        }

        $backup = $this->backupService->createBackup($argName);
        $info = $this->backupService->getBackupInfo($backup);

        $output->writeln(sprintf('Created new backup: %s, %s %s', $info['label'], $info['size'], $info['format']));
    }
}