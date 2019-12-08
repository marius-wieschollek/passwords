<?php

namespace OCA\Passwords\Cron;

use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class CleanUpSessions
 *
 * @package OCA\Passwords\Cron
 */
class CleanUpSessions extends AbstractCronJob {

    /**
     * @var SessionMapper
     */
    protected $sessionMapper;

    /**
     * CleanUpSessions constructor.
     *
     * @param LoggingService     $logger
     * @param EnvironmentService $environment
     * @param SessionMapper      $sessionMapper
     */
    public function __construct(LoggingService $logger, EnvironmentService $environment, SessionMapper $sessionMapper) {
        parent::__construct($logger, $environment);
        $this->sessionMapper = $sessionMapper;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
        $sessions = $this->sessionMapper->findAllOlderThan(time() - 15*60);

        foreach($sessions as $session) $this->sessionMapper->delete($session);

        $this->logger->info(['Closed and deleted %s sessions', count($sessions)]);
    }
}