<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

/** @noinspection PhpMissingFieldTypeInspection */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Exception\Migration\NextcloudRequirementNotMetException;
use OCA\Passwords\Exception\Migration\PhpRequirementNotMetException;
use OCA\Passwords\Exception\Migration\UpgradeUnsupportedException;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CheckAppRequirements
 *
 * @package OCA\Passwords\Migration
 */
class CheckAppRequirements implements IRepairStep {

    const UPGRADE_MINIMUM_APP_VERSION   = '2020.1.0';
    const PHP_MINIMUM_REQUIREMENT_ID    = 70400;
    const PHP_MINIMUM_REQUIREMENT       = '7.4.0';
    const NEXTCLOUD_MINIMUM_REQUIREMENT = 20;

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * CheckAppRequirements constructor.
     *
     * @param IConfig $config
     */
    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getName() {
        return 'Check System Requirements';
    }

    /**
     * @param IOutput $output
     *
     * @throws NextcloudRequirementNotMetException
     * @throws PhpRequirementNotMetException
     * @throws UpgradeUnsupportedException
     */
    public function run(IOutput $output) {
        $this->canInstallRelease();
        $this->canUpgradeFromPreviousVersion();
    }

    /**
     * @throws UpgradeUnsupportedException if the previous version is below the minimum requirement
     */
    protected function canUpgradeFromPreviousVersion() {
        $previousVersion = $this->config->getAppValue('passwords', 'installed_version', '0.0.0');
        if($previousVersion === '0.0.0') return;

        if(version_compare(self::UPGRADE_MINIMUM_APP_VERSION, $previousVersion) === 1) {
            throw new UpgradeUnsupportedException($previousVersion, self::UPGRADE_MINIMUM_APP_VERSION);
        }
    }

    /**
     * @throws NextcloudRequirementNotMetException if the used version of Nextcloud is too low
     * @throws PhpRequirementNotMetException if the used version of PHP is too low
     */
    protected function canInstallRelease() {
        if(PHP_VERSION_ID < self::PHP_MINIMUM_REQUIREMENT_ID) {
            throw new PhpRequirementNotMetException(PHP_VERSION, self::PHP_MINIMUM_REQUIREMENT);
        }

        $ncVersion = intval(explode('.', $this->config->getSystemValue('version'), 2)[0]);
        if($ncVersion < self::NEXTCLOUD_MINIMUM_REQUIREMENT) {
            throw new NextcloudRequirementNotMetException($ncVersion, self::NEXTCLOUD_MINIMUM_REQUIREMENT);
        }
    }
}