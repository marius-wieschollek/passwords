<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use Exception;
use OC\BackgroundJob\TimedJob;
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
    protected $logger;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var float|int
     */
    protected $interval = 1;

    /**
     * AbstractCronJob constructor.
     *
     * @param LoggingService     $logger
     * @param EnvironmentService $environment
     */
    public function __construct(
        LoggingService $logger,
        EnvironmentService $environment
    ) {
        $this->logger      = $logger;
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

        try {
            $this->runJob($argument);
        } catch(Exception $e) {
            $this->logger->logException($e);
        }
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    abstract protected function runJob($argument): void;
}