<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\CleanUp;

use OCA\Passwords\Db\RegistrationMapper;
use OCA\Passwords\Services\LoggingService;

/**
 * Class CleanRegistrationsHelper
 *
 * @package OCA\Passwords\Helper\CleanUp
 */
class CleanRegistrationsHelper {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var RegistrationMapper
     */
    protected $registrationMapper;

    /**
     * CleanSessionsHelper constructor.
     *
     * @param LoggingService     $logger
     * @param RegistrationMapper $registrationMapper
     */
    public function __construct(LoggingService $logger, RegistrationMapper $registrationMapper) {
        $this->registrationMapper = $registrationMapper;
        $this->logger             = $logger;
    }

    /**
     *
     */
    public function run(): void {
        $registrations = $this->registrationMapper->findAllOlderThan(time() - 150);

        foreach($registrations as $session) $this->registrationMapper->delete($session);

        $total = count($registrations);
        $this->logger->debugOrInfo(['Deleted %s registrations permanently', $total], $total);
    }
}