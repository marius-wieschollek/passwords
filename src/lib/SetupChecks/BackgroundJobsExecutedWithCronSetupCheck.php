<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\SetupChecks;

use OCA\Passwords\Services\ConfigurationService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class BackgroundJobsExecutedWithCronSetupCheck implements ISetupCheck {

    public function __construct(
        protected IL10N                $l10n,
        protected IURLGenerator        $urlGenerator,
        protected ConfigurationService $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCategory(): string {
        return 'passwords';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return $this->l10n->t('Checking if the server runs background jobs with cron.');
    }

    /**
     * @inheritDoc
     */
    public function run(): SetupResult {
        $cronType = $this->config->getAppValue('backgroundjobs_mode', 'ajax', 'core');

        if($cronType !== 'cron') {
            $text = $this->l10n->t('Using %s to execute background jobs may cause delays. We recommend using Cron.', [ucfirst($cronType)]);

            return SetupResult::warning(
                $text,
                $this->urlGenerator->linkToDocs('admin-background-jobs')
            );
        }

        return SetupResult::success($this->l10n->t('Background jobs are executed with cron.'));
    }
}