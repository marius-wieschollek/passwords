<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Services\BackupService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupDeleteCommand
 *
 * @package OCA\Passwords\Command
 */
class BackupDeleteCommand extends Command {

    /**
     * @var BackupService
     */
    protected BackupService $backupService;

    /**
     * BackupDeleteCommand constructor.
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
        $this->setName('passwords:backup:delete')
             ->addArgument('name', InputArgument::REQUIRED, 'The backup to delete')
             ->setDescription('Delete a manually created backup file');
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
        $backup  = $input->getArgument('name');
        $backups = $this->backupService->getBackups('backups');

        if(isset($backups[ $backup ])) {
            $backups[ $backup ]->delete();

            $output->writeln('Deleted: '.$backup);
        } else {
            throw new NotFoundException();
        }

        return 0;
    }
}