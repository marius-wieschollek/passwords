<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Migration;

use Exception;
use OCA\Passwords\Exception\Migration\UpgradeUnsupportedException;
use OCA\Passwords\Migration\DatabaseRepair\FolderModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\FolderRevisionRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordRevisionRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordTagRelationRepair;
use OCA\Passwords\Migration\DatabaseRepair\TagModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\TagRevisionRepair;
use OCA\Passwords\Services\ConfigurationService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class DatabaseObjectRepair
 *
 * @package OCA\Passwords\Migration
 */
class DatabaseObjectRepair implements IRepairStep {

    const MINIMUM_UPGRADE_VERSION = '2020.1.0';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var TagModelRepair
     */
    protected TagModelRepair $tagModelRepair;

    /**
     * @var FolderModelRepair
     */
    protected FolderModelRepair $folderModelRepair;

    /**
     * @var TagRevisionRepair
     */
    protected TagRevisionRepair $tagRevisionRepair;

    /**
     * @var PasswordModelRepair
     */
    protected PasswordModelRepair $passwordModelRepair;

    /**
     * @var FolderRevisionRepair
     */
    protected FolderRevisionRepair $folderRevisionRepair;

    /**
     * @var PasswordRevisionRepair
     */
    protected PasswordRevisionRepair $passwordRevisionRepair;

    /**
     * @var PasswordTagRelationRepair
     */
    protected PasswordTagRelationRepair $passwordTagRelationRepair;

    /**
     * DatabaseObjectRepair constructor.
     *
     * @param ConfigurationService      $config
     * @param TagModelRepair            $tagModelRepair
     * @param FolderModelRepair         $folderModelRepair
     * @param TagRevisionRepair         $tagRevisionRepair
     * @param PasswordModelRepair       $passwordModelRepair
     * @param FolderRevisionRepair      $folderRevisionRepair
     * @param PasswordRevisionRepair    $passwordRevisionRepair
     * @param PasswordTagRelationRepair $passwordTagRelationRepair
     */
    public function __construct(
        ConfigurationService $config,
        TagModelRepair $tagModelRepair,
        FolderModelRepair $folderModelRepair,
        TagRevisionRepair $tagRevisionRepair,
        PasswordModelRepair $passwordModelRepair,
        FolderRevisionRepair $folderRevisionRepair,
        PasswordRevisionRepair $passwordRevisionRepair,
        PasswordTagRelationRepair $passwordTagRelationRepair
    ) {
        $this->config                    = $config;
        $this->tagModelRepair            = $tagModelRepair;
        $this->folderModelRepair         = $folderModelRepair;
        $this->tagRevisionRepair         = $tagRevisionRepair;
        $this->passwordModelRepair       = $passwordModelRepair;
        $this->folderRevisionRepair      = $folderRevisionRepair;
        $this->passwordRevisionRepair    = $passwordRevisionRepair;
        $this->passwordTagRelationRepair = $passwordTagRelationRepair;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName(): string {
        return 'Repair Passwords Database Objects';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output): void {
        $this->canUpgradeFromPreviousVersion();

        $this->tagRevisionRepair->run($output);
        $this->folderRevisionRepair->run($output);
        $this->passwordRevisionRepair->run($output);
        $this->tagModelRepair->run($output);
        $this->folderModelRepair->run($output);
        $this->passwordModelRepair->run($output);
        $this->passwordTagRelationRepair->run($output);
    }

    /**
     * @throws UpgradeUnsupportedException if the previous version is below the minimum requirement
     */
    protected function canUpgradeFromPreviousVersion() {
        $previousVersion = $this->config->getAppValue('installed_version', '0.0.0');
        if($previousVersion === '0.0.0') return;

        if(version_compare(self::MINIMUM_UPGRADE_VERSION, $previousVersion) === 1) {
            throw new UpgradeUnsupportedException($previousVersion, self::MINIMUM_UPGRADE_VERSION);
        }
    }
}