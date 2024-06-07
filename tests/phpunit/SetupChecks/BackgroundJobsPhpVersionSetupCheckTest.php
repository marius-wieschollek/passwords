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

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

class BackgroundJobsPhpVersionSetupCheckTest extends TestCase {

    public function testRunSuccess() {
        $l10n = $this->createMock(IL10N::class);
        $l10n->expects($this->once())->method('t')->with('Background jobs are executed with the same PHP version.')->willReturn('SuccessText');

        $config = $this->createMock(ConfigurationService::class);
        $config->expects($this->atLeast(2))->method('getAppValue')->willReturnMap(
            [
                ['cron/php/version/id', PHP_VERSION_ID, Application::APP_NAME, strval(PHP_VERSION_ID)],
                ['cron/php/version/string', phpversion(), Application::APP_NAME, phpversion()]
            ]
        );

        $setupCheck = new BackgroundJobsPhpVersionSetupCheck(
            $l10n,
            $config,
        );

        $setupCheck->run();
    }

    public function testRunError() {
        $l10n = $this->createMock(IL10N::class);
        $l10n->expects($this->atLeast(2))
             ->method('t')
             ->willReturnMap(
                 [
                     ['The last background job was executed with PHP %1$s, but the webserver uses PHP %2$s.', ['7.0.0', phpversion()], 'ErrorText1'],
                     ['Using different major versions of PHP may cause issues.', [], 'ErrorText2']
                 ]
             );

        $config = $this->createMock(ConfigurationService::class);
        $config->expects($this->atLeast(2))->method('getAppValue')->willReturnMap(
            [
                ['cron/php/version/id', PHP_VERSION_ID, Application::APP_NAME, '70000'],
                ['cron/php/version/string', phpversion(), Application::APP_NAME, '7.0.0']
            ]
        );

        $setupCheck = new BackgroundJobsPhpVersionSetupCheck(
            $l10n,
            $config,
        );

        $setupCheck->run();
    }
}