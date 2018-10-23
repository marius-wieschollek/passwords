<?php

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

        parent::__construct(null);
    }

    /**
     *
     */
    protected function configure() {
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
    protected function execute(InputInterface $input, OutputInterface $output) {
        $backups = $this->backupService->getBackups();

        if(empty($backups)) $output->writeln('No backups found');

        $output->writeln('The following backups are available:');
        foreach($backups as $backup) {
            $name = $backup->getName();
            $name = substr($name, 0, strpos($name, '.json'));
            $size = str_pad(\OC_Helper::humanFileSize($backup->getSize()), 7, ' ', STR_PAD_LEFT);
            $gzip = substr($backup->getName(), -2) === 'gz' ? 'compressed':'json';

            $output->writeln(sprintf('   %s  %s %s', $name, $size, $gzip));
        }
    }
}