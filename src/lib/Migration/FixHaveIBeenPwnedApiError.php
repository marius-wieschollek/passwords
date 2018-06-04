<?php

namespace OCA\Passwords\Migration;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\LoggingService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class FixHaveIBeenPwnedApiError
 *
 * @package OCA\Passwords\Migration
 */
class FixHaveIBeenPwnedApiError implements IRepairStep {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var PasswordRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * FixHaveIBeenPwnedApiError constructor.
     *
     * @param LoggingService         $logger
     * @param ConfigurationService   $config
     * @param FileCacheService       $fileCacheService
     * @param PasswordRevisionMapper $revisionMapper
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        FileCacheService $fileCacheService,
        PasswordRevisionMapper $revisionMapper
    ) {
        $this->logger           = $logger;
        $this->config           = $config;
        $this->revisionMapper   = $revisionMapper;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Fix invalid password status when hibp enabled';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        $wasAlreadyExecuted = intval($this->config->getAppValue('migration/hibp', 0)) == 1;
        if($wasAlreadyExecuted) return;

        $securityService = $this->config->getAppValue('service/security', HelperService::SECURITY_HIBP);
        if($securityService != HelperService::SECURITY_HIBP && $securityService != HelperService::SECURITY_BIGDB_HIBP) {
            $this->config->setAppValue('migration/hibp', 1);

            return;
        }

        $this->resetPasswordSecurityStatus($output);
    }

    /**
     * @param IOutput $output
     *
     * @throws \Exception
     */
    protected function resetPasswordSecurityStatus(IOutput $output): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionMapper->findAllMatching(['status', 2]);

        $count     = count($revisions);
        $output->info("Resetting Password Security Status (total: {$count})");
        $output->startProgress($count);

        $this->fileCacheService->clearCache(FileCacheService::PASSWORDS_CACHE);

        foreach($revisions as $revision) {
            $revision->setStatus(0);
            $this->revisionMapper->update($revision);
            $output->advance(1);
        }
        $output->finishProgress();

        $this->config->setAppValue('migration/hibp', 1);
        $this->logger->info('Password security status has been reset. Correct status will be reported once the background job was executed. See https://github.com/marius-wieschollek/passwords/issues/50 for details');
    }
}