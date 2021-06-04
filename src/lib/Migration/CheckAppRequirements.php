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

use OCA\Passwords\AppInfo\SystemRequirements;
use OCA\Passwords\Exception\Migration\PhpRequirementNotMetException;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CheckAppRequirements
 *
 * @package OCA\Passwords\Migration
 */
class CheckAppRequirements implements IRepairStep {

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
     * @throws PhpRequirementNotMetException
     */
    public function run(IOutput $output) {
        $this->canInstallRelease();
    }

    /**
     * @throws PhpRequirementNotMetException if the used version of PHP is too low
     */
    protected function canInstallRelease() {
        if(PHP_VERSION_ID < SystemRequirements::PHP_MINIMUM_ID) {
            throw new PhpRequirementNotMetException(PHP_VERSION, SystemRequirements::PHP_MINIMUM);
        }
    }
}