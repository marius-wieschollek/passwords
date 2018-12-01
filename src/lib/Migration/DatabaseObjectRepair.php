<?php

namespace OCA\Passwords\Migration;

use OCA\Passwords\Migration\DatabaseRepair\FolderModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\FolderRevisionRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordRevisionRepair;
use OCA\Passwords\Migration\DatabaseRepair\PasswordTagRelationRepair;
use OCA\Passwords\Migration\DatabaseRepair\TagModelRepair;
use OCA\Passwords\Migration\DatabaseRepair\TagRevisionRepair;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class DatabaseObjectRepair
 *
 * @package OCA\Passwords\Migration
 */
class DatabaseObjectRepair implements IRepairStep {

    /**
     * @var TagModelRepair
     */
    protected $tagModelRepair;

    /**
     * @var FolderModelRepair
     */
    protected $folderModelRepair;

    /**
     * @var TagRevisionRepair
     */
    protected $tagRevisionRepair;

    /**
     * @var PasswordModelRepair
     */
    protected $passwordModelRepair;

    /**
     * @var FolderRevisionRepair
     */
    protected $folderRevisionRepair;

    /**
     * @var PasswordRevisionRepair
     */
    protected $passwordRevisionRepair;

    /**
     * @var PasswordTagRelationRepair
     */
    protected $passwordTagRelationRepair;

    /**
     * DatabaseObjectRepair constructor.
     *
     * @param TagModelRepair            $tagModelRepair
     * @param FolderModelRepair         $folderModelRepair
     * @param TagRevisionRepair         $tagRevisionRepair
     * @param PasswordModelRepair       $passwordModelRepair
     * @param FolderRevisionRepair      $folderRevisionRepair
     * @param PasswordRevisionRepair    $passwordRevisionRepair
     * @param PasswordTagRelationRepair $passwordTagRelationRepair
     */
    public function __construct(
        TagModelRepair $tagModelRepair,
        FolderModelRepair $folderModelRepair,
        TagRevisionRepair $tagRevisionRepair,
        PasswordModelRepair $passwordModelRepair,
        FolderRevisionRepair $folderRevisionRepair,
        PasswordRevisionRepair $passwordRevisionRepair,
        PasswordTagRelationRepair $passwordTagRelationRepair
    ) {
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
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output): void {
        $this->tagRevisionRepair->run($output);
        $this->folderRevisionRepair->run($output);
        $this->passwordRevisionRepair->run($output);
        $this->tagModelRepair->run($output);
        $this->folderModelRepair->run($output);
        $this->passwordModelRepair->run($output);
        $this->passwordTagRelationRepair->run($output);
    }
}