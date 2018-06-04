<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

//use OCP\Migration\IMigrationStep;

/**
 * Class UpdateDatabaseFields
 *
 * @package OCA\Passwords\Migration
 * @TODO    Use IMigrationStep after dropping NC 12.x
 */
class UpdateDatabaseFields implements IRepairStep {

    /**
     * @var array
     */
    protected static $migrationExecuted = [];

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * UpdateDatabaseFields constructor.
     *
     * @param LoggingService          $logger
     * @param ConfigurationService    $config
     * @param EnvironmentService      $environment
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        EnvironmentService $environment,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->config                  = $config;
        $this->logger                  = $logger;
        $this->environment             = $environment;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Update Database Passwords Fields';
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
        if(!$this->environment->isGlobalMode()) {
            $this->logger->error('User mode detected. Use ./occ upgrade to upgrade');

            return;
        }

        $databaseVersion = intval($this->config->getAppValue('database_version', 0));
        if($databaseVersion < 1) $this->executeMigration('createCustomFields', $output);
    }

    /**
     * @param IOutput  $output
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array    $options
     *
     * @since 13.0.0
     * @throws \Exception
     */
    public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
        $this->run($output);
    }

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
     * @param string  $name
     * @param IOutput $output
     */
    protected function executeMigration(string $name, IOutput $output): void {
        if(!isset(self::$migrationExecuted[ $name ]) || !self::$migrationExecuted[ $name ]) {
            $this->{$name}($output);
            self::$migrationExecuted[ $name ] = true;
            $this->logger->info('Executed Migration: '.$name);
        }
    }

    /**
     * @param IOutput $output
     *
     * @throws \Exception
     */
    protected function createCustomFields(IOutput $output): void {
        /** @var PasswordRevision[] $passwordRevisions */
        $passwordRevisions = $this->passwordRevisionService->findAll(true);

        $count = count($passwordRevisions);
        $output->info("Adding Custom Fields to Revisions (total: {$count})");
        $output->startProgress($count);
        foreach($passwordRevisions as $passwordRevision) {
            try {
                if($passwordRevision->getCustomFields() === null && $passwordRevision->getCseType() === 'none') {
                    $passwordRevision->setCustomFields('{}');
                    $this->passwordRevisionService->save($passwordRevision);
                }
            } catch(\Throwable $e) {
                $output->warning(
                    "Failed updating revision #{$passwordRevision->getUuid()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
        $this->setDatabaseVersion(1);
    }

    /**
     * @param int $version
     */
    protected function setDatabaseVersion(int $version): void {
        $databaseVersion = intval($this->config->getAppValue('database_version', 0));

        if($databaseVersion < $version) $this->config->setAppValue('database_version', 1);
    }
}