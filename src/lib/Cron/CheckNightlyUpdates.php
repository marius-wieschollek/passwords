<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Cron;

use Exception;
use OCA\Passwords\Fetcher\NightlyAppFetcher;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Class CheckNightlyUpdates
 *
 * @package OCA\Passwords\Cron
 */
class CheckNightlyUpdates extends AbstractTimedJob {

    /**
     * CheckNightlyUpdates constructor.
     *
     * @param ITimeFactory         $time
     * @param ConfigurationService $config
     * @param LoggingService       $logger
     * @param EnvironmentService   $environment
     * @param NightlyAppFetcher    $nightlyAppFetcher
     */
    public function __construct(
        ITimeFactory                $time,
        ConfigurationService        $config,
        LoggingService              $logger,
        EnvironmentService          $environment,
        protected NightlyAppFetcher $nightlyAppFetcher
    ) {
        parent::__construct($time, $logger, $config, $environment);
        $this->setInterval(0);
    }

    /**
     * @param $argument
     *
     * @throws Exception
     */
    protected function runJob($argument): void {
        if($this->config->getAppValue('nightly/enabled', '0') === '1') {
            $this->nightlyAppFetcher->get();
            if($this->nightlyAppFetcher->isDbUpdated()) $this->logger->debug('Fetched latest app database');
        }
    }
}