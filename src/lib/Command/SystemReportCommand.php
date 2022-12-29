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
             ->addArgument('sections', InputArgument::IS_ARRAY, 'Only print the given section(s)')
             ->addOption('basic', 'b', InputOption::VALUE_NONE, 'Only print basic report');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $sections = $input->getArgument('sections');
        $enhanced = !$input->getOption('basic') || !empty($sections);

        $data = $this->serverReportHelper->getReport($enhanced);
        if(!empty($sections)) {
            if(in_array('debug', $sections)) {
                $sections = ['version', 'environment', 'services', 'status', 'settings', 'encryption'];
            }

            $sectionData = [];
            foreach($sections as $section) {
                if(isset($data[ $section ])) {
                    $sectionData[ $section ] = $data[ $section ];
                }
            }

            $json = json_encode($sectionData, JSON_PRETTY_PRINT);
        } else {
            $json = json_encode($data, JSON_PRETTY_PRINT);
        }

        $output->write($json, true);

        return 0;
    }
}