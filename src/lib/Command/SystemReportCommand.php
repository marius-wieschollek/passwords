<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use OCA\Passwords\Helper\Survey\ServerReportHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SystemReportCommand
 *
 * @package OCA\Passwords\Command
 */
class SystemReportCommand extends Command {

    /**
     * @var ServerReportHelper
     */
    protected ServerReportHelper $serverReportHelper;

    /**
     * SystemReportCommand constructor.
     *
     * @param ServerReportHelper $serverReportHelper
     */
    public function __construct(ServerReportHelper $serverReportHelper) {
        parent::__construct();
        $this->serverReportHelper = $serverReportHelper;
    }

    /**
     *
     */
    protected function configure(): void {
        $this->setName('passwords:system:report')
             ->setDescription('Print system information as detected by the app')
             ->addArgument('section', InputArgument::OPTIONAL, 'Only print the given section')
             ->addOption('basic', 'b', InputOption::VALUE_NONE, 'Only print basic report')
             ->setHidden(true);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void {
        $section = $input->getArgument('section');
        $basic   = !$input->getOption('basic') && $section === null;

        $data = $this->serverReportHelper->getReport($basic);
        if($section !== null) {
            if(isset($data[ $section ])) {
                $json = json_encode($data[ $section ], JSON_PRETTY_PRINT);
            } else {
                $json = '{}';
            }
        } else {
            $json = json_encode($data, JSON_PRETTY_PRINT);
        }

        $output->write($json, true);
    }
}