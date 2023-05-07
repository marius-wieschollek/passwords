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
use OCA\Passwords\Services\BackgroundJobService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

/**
 * Class AbstractQueuedJob
 *
 * @package OCA\Passwords\Cron
 */
abstract class AbstractQueuedJob extends QueuedJob {

    /**
     * AbstractCronJob constructor.
     *
     * @param ITimeFactory         $time
     * @param LoggingService       $logger
     * @param EnvironmentService   $environment
     * @param BackgroundJobService $backgroundJobService
     */
    public function __construct(
        ITimeFactory $time,
        protected LoggingService $logger,
        protected EnvironmentService $environment,
        protected BackgroundJobService $backgroundJobService
    ) {
        parent::__construct($time);
    }

    /**
     * @param $argument
     */
    protected function run($argument): void {
        if($this->environment->getRunType() !== EnvironmentService::TYPE_CRON) {
            $this->logger->error(get_class($this).' must be executed as cron job');
            $this->backgroundJobService->add($this, $argument);

            return;
        }

        try {
            $this->runJob($argument);
        } catch(Exception $e) {
            $this->logger->logException($e);
            $this->backgroundJobService->add($this, $argument);
        }
    }

    /**
     * @param $argument
     *
     * @throws Exception
     */
    abstract protected function runJob($argument): void;
}