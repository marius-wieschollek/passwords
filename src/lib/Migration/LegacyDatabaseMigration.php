<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.12.17
 * Time: 22:16
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Migration\Legacy\LegacyCategoryMigration;
use OCA\Passwords\Migration\Legacy\LegacyPasswordMigration;
use OCA\Passwords\Migration\Legacy\LegacyShareMigration;
use OCA\Passwords\Services\ConfigurationService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class LegacyDatabaseMigration
 *
 * @package OCA\Passwords\Migration
 */
class LegacyDatabaseMigration implements IRepairStep {

    /**
     * @var LegacyShareMigration
     */
    protected $shareMigration;

    /**
     * @var LegacyPasswordMigration
     */
    protected $passwordMigration;

    /**
     * @var LegacyCategoryMigration
     */
    protected $categoryMigration;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * LegacyDatabaseMigration constructor.
     *
     * @param ConfigurationService    $configurationService
     * @param LegacyCategoryMigration $categoryMigration
     * @param LegacyPasswordMigration $passwordMigration
     * @param LegacyShareMigration    $shareMigration
     */
    public function __construct(
        ConfigurationService $configurationService,
        LegacyCategoryMigration $categoryMigration,
        LegacyPasswordMigration $passwordMigration,
        LegacyShareMigration $shareMigration
    ) {
        $this->passwordMigration    = $passwordMigration;
        $this->categoryMigration    = $categoryMigration;
        $this->configurationService = $configurationService;
        $this->shareMigration       = $shareMigration;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName(): string {
        return 'Passwords Legacy Database Migration';
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
        $version = $this->configurationService->getAppValue('installed_version');

        if(version_compare($version, '2018.0.0') < 0) {
            $tags   = $this->categoryMigration->migrateCategories($output);
            $shares = $this->passwordMigration->migratePasswords($output, $tags);
            $this->shareMigration->migratePasswords($output, $shares);
        } else {
            $output->info('Legacy upgrade not available for version '.$version);
        }
    }
}