<?php

declare(strict_types=1);

namespace OCA\Passwords\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version20260804
 *
 * @package OCA\Passwords\Migration
 */
class Version20260804 extends SimpleMigrationStep {
    /**
     * Human readable name of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function name(): string {
        return 'Add support for group shares';
    }

    /**
     * Human readable description of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function description(): string {
        return 'Add the parent_share field to the shares table which links a share created for a group member to the group share';
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     *
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('passwords_share');

        if(!$table->hasColumn('parent_share')) {
            $output->info('Creating column parent_share in table passwords_share');
            $table->addColumn(
                'parent_share',
                'string',
                [
                    'notnull' => false,
                    'default' => null,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasIndex('share_parent_share_index')) {
            $output->info('Creating index share_parent_share_index in table passwords_share');
            $table->addIndex(['parent_share'], 'share_parent_share_index');
        }

        return $schema;
    }
}
