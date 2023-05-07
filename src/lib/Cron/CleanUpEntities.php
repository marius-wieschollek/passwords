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

use OCA\Passwords\Helper\CleanUp\CleanDeletedEntitiesHelper;
use OCA\Passwords\Helper\CleanUp\CleanRegistrationsHelper;
use OCA\Passwords\Helper\CleanUp\CleanSessionsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Class CleanUpEntities
 *
 * @package OCA\Passwords\Cron
 */
class CleanUpEntities extends AbstractTimedJob {

    /**
     * CleanUpEntities constructor.
     *
     * @param ITimeFactory               $time
     * @param LoggingService             $logger
     * @param ConfigurationService       $config
     * @param EnvironmentService         $environment
     * @param CleanSessionsHelper        $sessionsHelper
     * @param CleanRegistrationsHelper   $registrationsHelper
     * @param CleanDeletedEntitiesHelper $deletedEntitiesHelper
     */
    public function __construct(
        ITimeFactory                         $time,
        LoggingService                       $logger,
        ConfigurationService                 $config,
        EnvironmentService                   $environment,
        protected CleanSessionsHelper        $sessionsHelper,
        protected CleanRegistrationsHelper   $registrationsHelper,
        protected CleanDeletedEntitiesHelper $deletedEntitiesHelper
    ) {
        parent::__construct($time, $logger, $config, $environment);
        $this->setInterval(15 * 60);
    }

    /**
     * @param $argument
     */
    protected function runJob($argument): void {
        try {
            $this->sessionsHelper->run();
        } catch(Throwable $e) {
            $this->logger->logException($e);
        }

        try {
            $this->registrationsHelper->run();
        } catch(Throwable $e) {
            $this->logger->logException($e);
        }

        try {
            $this->deletedEntitiesHelper->run();
        } catch(Throwable $e) {
            $this->logger->logException($e);
        }
    }
}