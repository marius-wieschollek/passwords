<?php

namespace OCA\Passwords\Command;

use OCA\Passwords\Services\BackupService;
use Symfony\Component\Console\Command\Command;
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

        parent::__construct(null);
    }

    /**
     *
     */
    protected function configure() {
        $this->setName('passwords:backup:create')
             ->setDescription('Create a new backup of the password database');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \OCP\Files\NotPermittedException
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $backup = $this->backupService->createBackup();

        $name = $backup->getName();
        $name = substr($name, 0, strpos($name, '.json'));
        $size = \OC_Helper::humanFileSize($backup->getSize());
        $gzip = substr($backup->getName(), -2) === 'gz' ? 'compressed':'json';

        $output->writeln(sprintf('Created new backup: %s, %s %s', $name, $size, $gzip));
    }
}