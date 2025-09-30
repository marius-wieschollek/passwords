<?php

declare(strict_types=1);

namespace OCA\Passwords\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version20250930
 *
 * @package OCA\Passwords\Migration
 */
class Version20250930 extends SimpleMigrationStep {
    /**
     * Human readable name of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function name(): string {
        return 'Add indexes to make sharing faster';
    }

    /**
     * Human readable description of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function description(): string {
        return 'Add indexes for uuid field of passwords table and source_password field of shares table';
    }

    /**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('passwords_password');

		if (!$table->hasIndex('pw_uuid_index')) {
			$table->addIndex(['uuid'], 'pw_uuid_index');
		}

        $table = $schema->getTable('passwords_share');
        if (!$table->hasIndex('share_source_password_index')) {
            $table->addIndex(['source_password'], 'share_source_password_index');
        }

		return $schema;
	}
}
