<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\CleanUp;

use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Services\LoggingService;

/**
 * Class CleanSessionsHelper
 *
 * @package OCA\Passwords\Helper\CleanUp
 */
class CleanSessionsHelper {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var SessionMapper
     */
    protected $sessionMapper;

    /**
     * CleanSessionsHelper constructor.
     *
     * @param LoggingService $logger
     * @param SessionMapper  $sessionMapper
     */
    public function __construct(LoggingService $logger, SessionMapper $sessionMapper) {
        $this->sessionMapper = $sessionMapper;
        $this->logger        = $logger;
    }

    /**
     *
     */
    public function run(): void {
        $sessions = $this->sessionMapper->findAllOlderThan(time() - 3600);

        foreach($sessions as $session) $this->sessionMapper->delete($session);

        $total = count($sessions);
        $this->logger->debugOrInfo(['Closed and deleted %s sessions permanently', $total], $total);
    }
}