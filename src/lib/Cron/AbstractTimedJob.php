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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
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
     * AbstractTimedJob constructor.
     *
     * @param ITimeFactory         $time
     * @param LoggingService       $logger
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        ITimeFactory $time,
        protected LoggingService $logger,
        protected ConfigurationService $config,
        protected EnvironmentService $environment
    ) {
        parent::__construct($time);
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
        $this->config->setAppValue('cron/php/version/string', phpversion());

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