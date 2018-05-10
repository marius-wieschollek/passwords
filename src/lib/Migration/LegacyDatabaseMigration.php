<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Migration\Legacy\LegacyCategoryMigration;
use OCA\Passwords\Migration\Legacy\LegacyPasswordMigration;
use OCA\Passwords\Migration\Legacy\LegacyShareMigration;
use OCA\Passwords\Services\ConfigurationService;
use OCP\DB\ISchemaWrapper;
//use OCP\Migration\IMigrationStep;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

/**
 * Class LegacyDatabaseMigration
 *
 * @package OCA\Passwords\Migration
 * @TODO Use IMigrationStep after dropping NC 12.x
 */
class LegacyDatabaseMigration implements /*IMigrationStep,*/ IRepairStep {

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
            if(PHP_VERSION_ID < 702000) {
                $tags   = $this->categoryMigration->migrateCategories($output);
                $shares = $this->passwordMigration->migratePasswords($output, $tags);
                $this->shareMigration->migratePasswords($output, $shares);
            } else {
                $output->info('Your PHP version is not supported by the migration. Use PHP 7.1.xx');
            }
        } else {
            $output->info('Legacy migration not available for version '.$version);
        }
    }

    /**
     * @param IOutput  $output
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array    $options
     *
     * @since 13.0.0
     * @throws \Exception
     */
    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $this->run($output);
    }

    /**
     * @param IOutput  $output
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array    $options
     *
     * @since 13.0.0
     */
    public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {}

    /**
     * @param IOutput  $output
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array    $options
     *
     * @return null|ISchemaWrapper
     * @since 13.0.0
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        return null;
    }
}