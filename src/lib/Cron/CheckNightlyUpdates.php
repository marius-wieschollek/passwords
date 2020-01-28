<?php

namespace OCA\Passwords\Cron;

use OCA\Passwords\Fetcher\NightlyAppFetcher;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class CheckNightlyUpdates
 *
 * @package OCA\Passwords\Cron
 */
class CheckNightlyUpdates extends AbstractTimedJob {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var NightlyAppFetcher
     */
    protected $nightlyAppFetcher;

    /**
     * @var float|int
     */
    protected $interval = 600;

    /**
     * CheckNightlyUpdates constructor.
     *
     * @param NightlyAppFetcher    $nightlyAppFetcher
     * @param ConfigurationService $config
     * @param LoggingService       $logger
     * @param EnvironmentService   $environment
     */
    public function __construct(NightlyAppFetcher $nightlyAppFetcher, ConfigurationService $config, LoggingService $logger, EnvironmentService $environment) {
        parent::__construct($logger, $environment);
        $this->nightlyAppFetcher = $nightlyAppFetcher;
        $this->config            = $config;
        $this->setInterval(0);
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
        if($this->config->getAppValue('nightly/enabled', '0') === '1') {
            $this->nightlyAppFetcher->get();
            if($this->nightlyAppFetcher->isDbUpdated()) $this->logger->debug('Fetched latest app database');
        }
    }
}