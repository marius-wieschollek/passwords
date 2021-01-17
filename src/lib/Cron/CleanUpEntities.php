<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OCA\Passwords\Helper\CleanUp\CleanDeletedEntitiesHelper;
use OCA\Passwords\Helper\CleanUp\CleanRegistrationsHelper;
use OCA\Passwords\Helper\CleanUp\CleanSessionsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use Throwable;

/**
 * Class CleanUpEntities
 *
 * @package OCA\Passwords\Cron
 */
class CleanUpEntities extends AbstractTimedJob {

    /**
     * @var CleanSessionsHelper
     */
    protected CleanSessionsHelper $sessionsHelper;

    /**
     * @var CleanRegistrationsHelper
     */
    protected CleanRegistrationsHelper $registrationsHelper;

    /**
     * @var CleanDeletedEntitiesHelper
     */
    protected CleanDeletedEntitiesHelper $deletedEntitiesHelper;

    /**
     * CleanUpEntities constructor.
     *
     * @param LoggingService             $logger
     * @param ConfigurationService       $config
     * @param EnvironmentService         $environment
     * @param CleanSessionsHelper        $sessionsHelper
     * @param CleanRegistrationsHelper   $registrationsHelper
     * @param CleanDeletedEntitiesHelper $deletedEntitiesHelper
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        EnvironmentService $environment,
        CleanSessionsHelper $sessionsHelper,
        CleanRegistrationsHelper $registrationsHelper,
        CleanDeletedEntitiesHelper $deletedEntitiesHelper
    ) {
        parent::__construct($logger, $config, $environment);
        $this->sessionsHelper        = $sessionsHelper;
        $this->registrationsHelper   = $registrationsHelper;
        $this->deletedEntitiesHelper = $deletedEntitiesHelper;
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