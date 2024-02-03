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
use PHPUnit\Framework\TestCase;

class BackgroundJobsExecutedWithCronSetupCheckTest extends TestCase {

    public function testRunSuccess() {
        $l10n = $this->createMock(IL10N::class);
        $l10n->expects($this->once())->method('t')->with('Background jobs are executed with cron.')->willReturn('SuccessText');

        $urlGenerator = $this->createMock(IURLGenerator::class);
        $urlGenerator->expects($this->never())->method('linkToDocs');

        $config = $this->createMock(ConfigurationService::class);
        $config->expects($this->once())->method('getAppValue')->willReturn('cron');

        $setupCheck = new BackgroundJobsExecutedWithCronSetupCheck(
            $l10n,
            $urlGenerator,
            $config,
        );

        $setupCheck->run();
    }

    public function testRunError() {
        $l10n = $this->createMock(IL10N::class);
        $l10n->expects($this->once())->method('t')->with('Using %s to execute background jobs may cause delays. We recommend using Cron.', ['Ajax'])->willReturn('ErrorText');

        $urlGenerator = $this->createMock(IURLGenerator::class);
        $urlGenerator->expects($this->once())->method('linkToDocs')->with('admin-background-jobs')->willReturn('https://example.com');

        $config = $this->createMock(ConfigurationService::class);
        $config->expects($this->once())->method('getAppValue')->willReturn('ajax');

        $setupCheck = new BackgroundJobsExecutedWithCronSetupCheck(
            $l10n,
            $urlGenerator,
            $config,
        );

        $setupCheck->run();
    }
}