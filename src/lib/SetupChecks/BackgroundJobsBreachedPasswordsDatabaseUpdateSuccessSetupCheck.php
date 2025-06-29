<?php
/*
 * @copyright 2025 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\SetupChecks;

use OCA\Passwords\Helper\SecurityCheck\PasswordDatabaseUpdateHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class BackgroundJobsBreachedPasswordsDatabaseUpdateSuccessSetupCheck implements ISetupCheck {

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
        return $this->l10n->t('Breached passwords database update');
    }

    /**
     * @inheritDoc
     */
    public function run(): SetupResult {
        $attempts = $this->config->getAppValueInt(PasswordDatabaseUpdateHelper::CONFIG_UPDATE_ATTEMPTS);

        if($attempts > 1) {
            $message = $this->config->getAppValue(PasswordDatabaseUpdateHelper::CONFIG_UPDATE_ERROR_MESSAGE, '');

            return SetupResult::warning(
                $this->l10n->t('Update of breached passwords database failed: %1$s', [$message])
            );
        }

        return SetupResult::success($this->l10n->t('Update of breached passwords database has not reported errors.'));
    }
}