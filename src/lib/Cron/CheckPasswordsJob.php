<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 30.09.17
 * Time: 21:24
 */

namespace OCA\Passwords\Cron;

use OC\BackgroundJob\TimedJob;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class CheckPasswordsJob
 *
 * @package OCA\Passwords\Cron
 */
class CheckPasswordsJob extends TimedJob {

    /**
     * @var HelperService
     */
    protected $helperService;

    /**
     * @var PasswordRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * CheckPasswordsJob constructor.
     *
     * @param LoggingService         $logger
     * @param HelperService          $helperService
     * @param PasswordRevisionMapper $revisionMapper
     */
    public function __construct(
        LoggingService $logger,
        HelperService $helperService,
        PasswordRevisionMapper $revisionMapper
    ) {
        // Run once per day
        $this->setInterval(24 * 60 * 60);
        $this->helperService  = $helperService;
        $this->revisionMapper = $revisionMapper;
        $this->logger = $logger;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function run($argument): void {
        $securityHelper = $this->helperService->getSecurityHelper();

        if($securityHelper->dbUpdateRequired()) {
            $securityHelper->updateDb();
        }
        $this->checkRevisionStatus($securityHelper);
    }

    /**
     * @param $securityHelper
     *
     * @throws \Exception
     */
    protected function checkRevisionStatus(AbstractSecurityCheckHelper $securityHelper): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionMapper->findAllMatching(['status', 2, '!=']);

        $badPasswordCounter = 0;
        foreach ($revisions as $revision) {
            $oldStatus = $revision->getStatus();
            $newStatus = $securityHelper->getRevisionSecurityLevel($revision);

            if($oldStatus != $newStatus) {
                $revision->setStatus($newStatus);
                $this->revisionMapper->update($revision);
                $badPasswordCounter++;
            }
        }

        $this->logger->info(['Checked %s passwords. %s new bad passwords found', count($revisions), $badPasswordCounter]);
    }
}