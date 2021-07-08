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

declare(strict_types=1);

namespace OCA\Passwords\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version20210801
 *
 * @package OCA\Passwords\Migration
 */
class Version20210801 extends SimpleMigrationStep {

    /**
     * Human readable name of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function name(): string {
        return 'Delete old tables';
    }

    /**
     * Human readable description of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function description(): string {
        return 'Deletes old tables used prior to NC 2021.7.0';
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     *
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        return $schemaClosure();
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     *
     * @throws \OCP\DB\Exception
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $tableMap = [
            'passwords_entity_challenge'         => 'passwords_challenge',
            'passwords_entity_folder'            => 'passwords_folder',
            'passwords_entity_folder_revision'   => 'passwords_folder_rv',
            'passwords_entity_keychain'          => 'passwords_keychain',
            'passwords_entity_password'          => 'passwords_password',
            'passwords_entity_password_revision' => 'passwords_password_rv',
            'passwords_entity_registration'      => 'passwords_registration',
            'passwords_entity_session'           => 'passwords_session',
            'passwords_entity_share'             => 'passwords_share',
            'passwords_entity_tag'               => 'passwords_tag',
            'passwords_entity_tag_revision'      => 'passwords_tag_rv',
            'passwords_relation_password_tag'    => 'passwords_pw_tag_rel'
        ];

        foreach($tableMap as $oldTable => $newTable) {
            if($schema->hasTable($oldTable) && $schema->hasTable($newTable)) {
                $output->info("Deleting old table {$oldTable}, replaced by {$newTable}");
                $schema->dropTable($oldTable);
            }

            $output->info('Done');
        }
    }
}
