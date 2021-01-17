<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use Exception;
use OC\BackgroundJob\TimedJob;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class AbstractCronJob
 *
 * @package OCA\Passwords\Cron
 */
abstract class AbstractTimedJob extends TimedJob {

    /**
     * @var LoggingService
     */
    protected LoggingService $logger;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * @var float|int
     */
    protected $interval = 1;

    /**
     * AbstractCronJob constructor.
     *
     * @param LoggingService       $logger
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        EnvironmentService $environment
    ) {
        $this->logger      = $logger;
        $this->config      = $config;
        $this->environment = $environment;
    }

    /**
     * @param $argument
     */
    protected function run($argument): void {
        if($this->environment->getRunType() !== EnvironmentService::TYPE_CRON) {
            $this->logger->error(get_class($this).' must be executed as cron job');

            return;
        }

        $this->config->setAppValue('cron/php/version/id', PHP_VERSION_ID);
        $this->config->setAppValue('cron/php/version/string', PHP_VERSION);

        try {
            $this->runJob($argument);
        } catch(Exception $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @param $argument
     *
     * @throws Exception
     */
    abstract protected function runJob($argument): void;
}