<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Migration;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IMigrationStep;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class CreateCustomFields
 *
 * @package OCA\Passwords\Migration
 */
class CreateCustomFields implements IMigrationStep, IRepairStep {

    protected static $isMigrated = false;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * CreateCustomFields constructor.
     *
     * @param PasswordRevisionService $passwordRevisionService
     * @param ConfigurationService    $config
     */
    public function __construct(PasswordRevisionService $passwordRevisionService, ConfigurationService $config) {
        $this->passwordRevisionService = $passwordRevisionService;
        $this->config = $config;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Create Custom Fields for Passwords';
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
        $version = $this->config->getAppValue('installed_version');
        if(version_compare($version, '2018.5.0') < 0 && !self::$isMigrated) $this->createCustomFields($output);
        self::$isMigrated = true;
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
     * @param IOutput $output
     *
     * @throws \Exception
     */
    protected function createCustomFields(IOutput $output): void {
        /** @var PasswordRevision[] $passwordRevisions */
        $passwordRevisions = $this->passwordRevisionService->findAll(true);

        $count = count($passwordRevisions);
        $output->info("Processing Revisions (total: {$count})");
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
    }
}