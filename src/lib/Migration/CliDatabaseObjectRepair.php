<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Migration;

use JetBrains\PhpStorm\Pure;
use OCA\Passwords\Migration\DatabaseRepair\FolderModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\FolderRevisionRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordRevisionRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordTagRelationRepair;
use OCA\Passwords\Migration\DatabaseRepair\ShareRepair;
use OCA\Passwords\Migration\DatabaseRepair\TagModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\TagRevisionRepair;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\Migration\IOutput;

class CliDatabaseObjectRepair extends DatabaseObjectRepair {

    /**
     * @param ConfigurationService      $config
     * @param ShareRepair               $shareRepair
     * @param TagModelRepair            $tagModelRepair
     * @param FolderModelRepair         $folderModelRepair
     * @param TagRevisionRepair         $tagRevisionRepair
     * @param EnvironmentService        $environmentService
     * @param PasswordModelRepair       $passwordModelRepair
     * @param FolderRevisionRepair      $folderRevisionRepair
     * @param PasswordRevisionRepair    $passwordRevisionRepair
     * @param PasswordTagRelationRepair $passwordTagRelationRepair
     */
    #[Pure] public function __construct(
        ConfigurationService      $config,
        ShareRepair               $shareRepair,
        TagModelRepair            $tagModelRepair,
        FolderModelRepair         $folderModelRepair,
        TagRevisionRepair         $tagRevisionRepair,
        protected EnvironmentService        $environmentService,
        PasswordModelRepair       $passwordModelRepair,
        FolderRevisionRepair      $folderRevisionRepair,
        PasswordRevisionRepair    $passwordRevisionRepair,
        PasswordTagRelationRepair $passwordTagRelationRepair
    ) {
        parent::__construct($config, $shareRepair, $tagModelRepair, $folderModelRepair, $tagRevisionRepair, $passwordModelRepair, $folderRevisionRepair, $passwordRevisionRepair, $passwordTagRelationRepair);
    }

    /**
     * @param IOutput $output
     *
     * @return void
     * @throws \Exception
     */
    public function run(IOutput $output): void {
        if($this->environmentService->getRunType() === EnvironmentService::TYPE_CLI) {
            parent::run($output);
        }
    }
}