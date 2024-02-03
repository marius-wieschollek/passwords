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
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class BackgroundJobsPhpVersionSetupCheck implements ISetupCheck {

    public function __construct(
        protected IL10N                $l10n,
        protected ConfigurationService $config
    ) {
    }

    public function getCategory(): string {
        return 'passwords';
    }

    public function getName(): string {
        return $this->l10n->t('Checking if the server runs background tasks with the same PHP version.');
    }

    public function run(): SetupResult {
        $cronPhpId     = $this->config->getAppValue('cron/php/version/id', PHP_VERSION_ID);
        $cronPhpString = $this->config->getAppValue('cron/php/version/string', phpversion());
        $isDifferent   = PHP_VERSION_ID - $cronPhpId > 99 || $cronPhpId - PHP_VERSION_ID > 99;

        if($isDifferent) {
            $text = $this->l10n->t('The last background job was executed with PHP %1$s, but the webserver uses PHP %2$s.', [$cronPhpString, phpversion()]).' '.
                    $this->l10n->t('Using different major versions of PHP may cause issues.');

            return SetupResult::warning($text);
        }

        return SetupResult::success($this->l10n->t('Background jobs are executed with the same PHP version.'));
    }
}