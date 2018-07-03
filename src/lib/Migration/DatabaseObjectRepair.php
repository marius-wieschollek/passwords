<?php

namespace OCA\Passwords\Migration;

use OCA\Passwords\Migration\DatabaseCleanup\FolderRevisionMigration;
use OCA\Passwords\Migration\DatabaseCleanup\PasswordRevisionMigration;
use OCA\Passwords\Migration\DatabaseCleanup\TagRevisionMigration;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class DeleteOrphanedObjects
 *
 * @package OCA\Passwords\Migration
 */
class DatabaseObjectRepair implements IRepairStep {

    /**
     * @var PasswordRevisionMigration
     */
    protected $passwordRevisionRepair;

    /**
     * @var FolderRevisionMigration
     */
    protected $folderRevisionRepair;

    /**
     * @var TagRevisionMigration
     */
    protected $tagRevisionRepair;

    /**
     * RepairDb constructor.
     *
     * @param PasswordRevisionMigration $passwordRevisionRepair
     * @param FolderRevisionMigration   $folderRevisionRepair
     * @param TagRevisionMigration      $tagRevisionRepair
     */
    public function __construct(
        PasswordRevisionMigration $passwordRevisionRepair,
        FolderRevisionMigration $folderRevisionRepair,
        TagRevisionMigration $tagRevisionRepair
    ) {
        $this->passwordRevisionRepair = $passwordRevisionRepair;
        $this->folderRevisionRepair   = $folderRevisionRepair;
        $this->tagRevisionRepair      = $tagRevisionRepair;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName(): string {
        return 'Repair Database Objects';
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
    }
}