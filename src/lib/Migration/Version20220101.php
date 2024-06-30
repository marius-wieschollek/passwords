<?php
/*
 * @copyright 2022 Passwords App
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
use Doctrine\DBAL\Schema\SchemaException;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version20220101
 *
 * @package OCA\Passwords\Migration
 */
class Version20220101 extends SimpleMigrationStep {

    /**
     * @var IDBConnection
     */
    protected IDBConnection $db;

    /**
     * @var UuidHelper
     */
    protected UuidHelper $uuidHelper;

    /**
     * Version20220101 constructor.
     *
     * @param IDBConnection $db
     * @param UuidHelper    $uuidHelper
     */
    public function __construct(IDBConnection $db, UuidHelper $uuidHelper) {
        $this->db         = $db;
        $this->uuidHelper = $uuidHelper;
    }

    /**
     * Human readable name of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function name(): string {
        return 'Create tables and move data';
    }

    /**
     * Human readable description of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function description(): string {
        return 'Creates new tables matching NC 22 spec and moves entries from old tables';
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
     * @throws SchemaException
     * @throws Exception
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $this->createPasswordModelTable($schema, $output);

        $this->createPasswordRevisionTable($schema, $output);

        $this->createFolderModelTable($schema, $output);

        $this->createFolderRevisionTable($schema, $output);

        $this->createTagModelTable($schema, $output);

        $this->createTagRevisionTable($schema, $output);

        $this->createPasswordTagRelationTable($schema, $output);

        $this->createShareTable($schema, $output);

        $this->createSessionTable($schema, $output);

        $this->createKeychainTable($schema, $output);

        $this->createChallengeTable($schema, $output);

        $this->createRegistrationTable($schema, $output);

        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     *
     * @throws Exception
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
                $select = $this->db->getQueryBuilder()->select('a.*')->from($oldTable, 'a');
                $uuids  = $this->getMigratedUuids($newTable);
                if(!empty($uuids) && $this->tablesMigrated()) {
                    return;
                }

                $result = $select->executeQuery();

                $total = $result->rowCount();
                $items = $result->fetchAll();

                $output->info("Migrating {$total} entries from {$oldTable} to {$newTable}");
                $output->startProgress($total);
                foreach($items as $item) {
                    $query = $this->db->getQueryBuilder()->insert($newTable);
                    if(empty($item['uuid'])) {
                        $item['uuid'] = $this->uuidHelper->generateUuid();
                    }

                    if(in_array($item['uuid'], $uuids)) {
                        $output->info("Skipping {$item['uuid']} because it exists in new table");
                        $output->advance($total);
                        continue;
                    }

                    // #662 Fix upgrade from extremly old versions of the app
                    if(isset($item['favourite'])) {
                        if(!isset($item['favorite'])) {
                            $item['favorite'] = $item['favourite'];
                        }
                        unset($item['favourite']);
                    }

                    foreach($item as $key => $value) {
                        if($key === 'id') continue;
                        if(!$schema->getTable($newTable)->hasColumn($key)) continue;

                        $type = $schema->getTable($newTable)->getColumn($key)->getType();
                        $query->setValue($key, $query->createNamedParameter($value, $type));
                    }

                    $query->executeStatement();
                    $output->advance($total);
                }
                $output->finishProgress();
                $output->info('Done');
            }
        }
    }

    /**
     * @param string $newTable
     *
     * @return array
     * @throws Exception
     */
    protected function getMigratedUuids(string $newTable): array {
        $select = $this->db->getQueryBuilder()->select('a.uuid')->from($newTable, 'a');
        $result = $select->executeQuery();
        $items  = $result->fetchAll();

        $uuids = [];
        foreach($items as $item) {
            $uuids[] = $item['uuid'];
        }

        return $uuids;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function tablesMigrated(): bool {
        $qb     = $this->db->getQueryBuilder();
        $select = $qb->select('a.*')
                     ->from('migrations', 'a')
                     ->where($qb->expr()->eq('a.app', $qb->createNamedParameter('passwords')))
                     ->where($qb->expr()->eq('a.version', $qb->createNamedParameter('20210800')));

        $result = $select->executeQuery();

        return $result->rowCount() > 0;
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createPasswordModelTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_password')) {
            $output->info('Creating table passwords_password');
            $table = $schema->createTable('passwords_password');
        } else {
            $table = $schema->getTable('passwords_password');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_password');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_password');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_password');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('revision')) {
            $output->info('Creating column revision in table passwords_password');
            $table->addColumn(
                'revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('share_id')) {
            $output->info('Creating column share_id in table passwords_password');
            $table->addColumn(
                'share_id',
                'string',
                [
                    'notnull' => false,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('has_shares')) {
            $output->info('Creating column has_shares in table passwords_password');
            $table->addColumn(
                'has_shares',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('editable')) {
            $output->info('Creating column editable in table passwords_password');
            $table->addColumn(
                'editable',
                'boolean',
                [
                    'notnull' => false,
                    'default' => true,
                ]
            );
        }

        if(!$table->hasColumn('suspended')) {
            $output->info('Creating column suspended in table passwords_password');
            $table->addColumn(
                'suspended',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_password');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_password');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_password');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_password');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_password_index')) {
            $output->info('Removing index pw_password_index from table passwords_password');
            $table->dropIndex('pw_password_index');
        }

        if(!$table->hasIndex('pw_index_password')) {
            $output->info('Creating index pw_index_password in table passwords_password');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_password');
        }

        if(!$table->hasIndex('pw_index_password_ac')) {
            $output->info('Creating index pw_index_password_ac in table passwords_password');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_password_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createPasswordRevisionTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_password_rv')) {
            $output->info('Creating table passwords_password_rv');
            $table = $schema->createTable('passwords_password_rv');
        } else {
            $table = $schema->getTable('passwords_password_rv');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_password_rv');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_password_rv');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_password_rv');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('model')) {
            $output->info('Creating column model in table passwords_password_rv');
            $table->addColumn(
                'model',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('folder')) {
            $output->info('Creating column folder in table passwords_password_rv');
            $table->addColumn(
                'folder',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('hidden')) {
            $output->info('Creating column hidden in table passwords_password_rv');
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('trashed')) {
            $output->info('Creating column trashed in table passwords_password_rv');
            $table->addColumn(
                'trashed',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('cse_type')) {
            $output->info('Creating column cse_type in table passwords_password_rv');
            $table->addColumn(
                'cse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
        }

        if(!$table->hasColumn('cse_key')) {
            $output->info('Creating column cse_key in table passwords_password_rv');
            $table->addColumn(
                'cse_key',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
        }

        if(!$table->hasColumn('sse_type')) {
            $output->info('Creating column sse_type in table passwords_password_rv');
            $table->addColumn(
                'sse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
        }

        if(!$table->hasColumn('sse_key')) {
            $output->info('Creating column sse_key in table passwords_password_rv');
            $table->addColumn(
                'sse_key',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('username')) {
            $output->info('Creating column username in table passwords_password_rv');
            $table->addColumn(
                'username',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('password')) {
            $output->info('Creating column password in table passwords_password_rv');
            $table->addColumn(
                'password',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('label')) {
            $output->info('Creating column label in table passwords_password_rv');
            $table->addColumn(
                'label',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('url')) {
            $output->info('Creating column url in table passwords_password_rv');
            $table->addColumn(
                'url',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('notes')) {
            $output->info('Creating column notes in table passwords_password_rv');
            $table->addColumn(
                'notes',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('custom_fields')) {
            $output->info('Creating column custom_fields in table passwords_password_rv');
            $table->addColumn(
                'custom_fields',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('hash')) {
            $output->info('Creating column hash in table passwords_password_rv');
            $table->addColumn(
                'hash',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('favorite')) {
            $output->info('Creating column favorite in table passwords_password_rv');
            $table->addColumn(
                'favorite',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('status')) {
            $output->info('Creating column status in table passwords_password_rv');
            $table->addColumn(
                'status',
                'smallint',
                [
                    'notnull' => true,
                    'length'  => 1,
                    'default' => 0,
                ]
            );
        }

        if(!$table->hasColumn('status_code')) {
            $output->info('Creating column status_code in table passwords_password_rv');
            $table->addColumn(
                'status_code',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                    'default' => 'GOOD',
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_password_rv');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('edited')) {
            $output->info('Creating column edited in table passwords_password_rv');
            $table->addColumn(
                'edited',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_password_rv');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_password_rv');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('client')) {
            $output->info('Creating column client in table passwords_password_rv');
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_password_rv');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_password_revision_index')) {
            $output->info('Removing index pw_password_revision_index from table passwords_password_rv');
            $table->dropIndex('pw_password_revision_index');
        }

        if($table->hasIndex('pw_index_password_revision')) {
            $output->info('Removing index pw_index_password_revision from table passwords_password_rv');
            $table->dropIndex('pw_index_password_revision');
        }

        if(!$table->hasIndex('pw_index_password_rv')) {
            $output->info('Creating index pw_index_password_rv in table passwords_password_rv');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_password_rv');
        }

        if(!$table->hasIndex('pw_index_password_rv_ac')) {
            $output->info('Creating index pw_index_password_rv_ac in table passwords_password_rv');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_password_rv_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createFolderModelTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_folder')) {
            $output->info('Creating table passwords_folder');
            $table = $schema->createTable('passwords_folder');
        } else {
            $table = $schema->getTable('passwords_folder');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_folder');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_folder');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_folder');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('revision')) {
            $output->info('Creating column revision in table passwords_folder');
            $table->addColumn(
                'revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('suspended')) {
            $output->info('Creating column suspended in table passwords_folder');
            $table->addColumn(
                'suspended',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_folder');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_folder');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_folder');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_folder');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_folder_index')) {
            $output->info('Removing index pw_folder_index from table passwords_folder');
            $table->dropIndex('pw_folder_index');
        }

        if(!$table->hasIndex('pw_index_folder')) {
            $output->info('Creating index pw_index_folder in table passwords_folder');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_folder');
        }

        if(!$table->hasIndex('pw_index_folder_ac')) {
            $output->info('Creating index pw_index_folder_ac in table passwords_folder');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_folder_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createFolderRevisionTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_folder_rv')) {
            $output->info('Creating table passwords_folder_rv');
            $table = $schema->createTable('passwords_folder_rv');
        } else {
            $table = $schema->getTable('passwords_folder_rv');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_folder_rv');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_folder_rv');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_folder_rv');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('model')) {
            $output->info('Creating column model in table passwords_folder_rv');
            $table->addColumn(
                'model',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('parent')) {
            $output->info('Creating column parent in table passwords_folder_rv');
            $table->addColumn(
                'parent',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('label')) {
            $output->info('Creating column label in table passwords_folder_rv');
            $table->addColumn(
                'label',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('cse_type')) {
            $output->info('Creating column cse_type in table passwords_folder_rv');
            $table->addColumn(
                'cse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
        }

        if(!$table->hasColumn('cse_key')) {
            $output->info('Creating column cse_key in table passwords_folder_rv');
            $table->addColumn(
                'cse_key',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
        }

        if(!$table->hasColumn('sse_type')) {
            $output->info('Creating column sse_type in table passwords_folder_rv');
            $table->addColumn(
                'sse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
        }

        if(!$table->hasColumn('sse_key')) {
            $output->info('Creating column sse_key in table passwords_folder_rv');
            $table->addColumn(
                'sse_key',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('favorite')) {
            $output->info('Creating column favorite in table passwords_folder_rv');
            $table->addColumn(
                'favorite',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('hidden')) {
            $output->info('Creating column hidden in table passwords_folder_rv');
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('trashed')) {
            $output->info('Creating column trashed in table passwords_folder_rv');
            $table->addColumn(
                'trashed',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_folder_rv');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('edited')) {
            $output->info('Creating column edited in table passwords_folder_rv');
            $table->addColumn(
                'edited',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_folder_rv');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_folder_rv');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('client')) {
            $output->info('Creating column client in table passwords_folder_rv');
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_folder_rv');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_folder_revision_index')) {
            $output->info('Removing index pw_folder_revision_index from table passwords_folder_rv');
            $table->dropIndex('pw_folder_revision_index');
        }

        if($table->hasIndex('pw_index_folder_revision')) {
            $output->info('Removing index pw_index_folder_revision from table passwords_folder_rv');
            $table->dropIndex('pw_index_folder_revision');
        }

        if(!$table->hasIndex('pw_index_folder_rv')) {
            $output->info('Creating index pw_index_folder_rv in table passwords_folder_rv');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_folder_rv');
        }

        if(!$table->hasIndex('pw_index_folder_rv_ac')) {
            $output->info('Creating index pw_index_folder_rv_ac in table passwords_folder_rv');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_folder_rv_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createTagModelTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_tag')) {
            $output->info('Creating table passwords_tag');
            $table = $schema->createTable('passwords_tag');
        } else {
            $table = $schema->getTable('passwords_tag');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_tag');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_tag');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_tag');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('revision')) {
            $output->info('Creating column revision in table passwords_tag');
            $table->addColumn(
                'revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_tag');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_tag');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_tag');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_tag');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_tag_index')) {
            $output->info('Removing index pw_tag_index from table passwords_tag');
            $table->dropIndex('pw_tag_index');
        }

        if(!$table->hasIndex('pw_index_tag')) {
            $output->info('Creating index pw_index_tag in table passwords_tag');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_tag');
        }

        if(!$table->hasIndex('pw_index_tag_ac')) {
            $output->info('Creating index pw_index_tag_ac in table passwords_tag');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_tag_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createTagRevisionTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_tag_rv')) {
            $output->info('Creating table passwords_tag_rv');
            $table = $schema->createTable('passwords_tag_rv');
        } else {
            $table = $schema->getTable('passwords_tag_rv');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_tag_rv');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_tag_rv');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_tag_rv');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('model')) {
            $output->info('Creating column model in table passwords_tag_rv');
            $table->addColumn(
                'model',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('cse_type')) {
            $output->info('Creating column cse_type in table passwords_tag_rv');
            $table->addColumn(
                'cse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
        }

        if(!$table->hasColumn('cse_key')) {
            $output->info('Creating column cse_key in table passwords_tag_rv');
            $table->addColumn(
                'cse_key',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
        }

        if(!$table->hasColumn('sse_type')) {
            $output->info('Creating column sse_type in table passwords_tag_rv');
            $table->addColumn(
                'sse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
        }

        if(!$table->hasColumn('sse_key')) {
            $output->info('Creating column sse_key in table passwords_tag_rv');
            $table->addColumn(
                'sse_key',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('label')) {
            $output->info('Creating column label in table passwords_tag_rv');
            $table->addColumn(
                'label',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('color')) {
            $output->info('Creating column color in table passwords_tag_rv');
            $table->addColumn(
                'color',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('favorite')) {
            $output->info('Creating column favorite in table passwords_tag_rv');
            $table->addColumn(
                'favorite',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('hidden')) {
            $output->info('Creating column hidden in table passwords_tag_rv');
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('trashed')) {
            $output->info('Creating column trashed in table passwords_tag_rv');
            $table->addColumn(
                'trashed',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_tag_rv');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('edited')) {
            $output->info('Creating column edited in table passwords_tag_rv');
            $table->addColumn(
                'edited',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_tag_rv');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_tag_rv');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('client')) {
            $output->info('Creating column client in table passwords_tag_rv');
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_tag_rv');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_tag_revision_index')) {
            $output->info('Removing index pw_tag_revision_index from table passwords_tag_rv');
            $table->dropIndex('pw_tag_revision_index');
        }

        if($table->hasIndex('pw_index_tag_revision')) {
            $output->info('Removing index pw_index_tag_revision from table passwords_tag_rv');
            $table->dropIndex('pw_index_tag_revision');
        }

        if(!$table->hasIndex('pw_index_tag_rv')) {
            $output->info('Creating index pw_index_tag_rv in table passwords_tag_rv');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_tag_rv');
        }

        if(!$table->hasIndex('pw_index_tag_rv_ac')) {
            $output->info('Creating index pw_index_tag_rv_ac in table passwords_tag_rv');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_tag_rv_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createPasswordTagRelationTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_pw_tag_rel')) {
            $output->info('Creating table passwords_pw_tag_rel');
            $table = $schema->createTable('passwords_pw_tag_rel');
        } else {
            $table = $schema->getTable('passwords_pw_tag_rel');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_pw_tag_rel');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_pw_tag_rel');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_pw_tag_rel');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
        }

        if(!$table->hasColumn('password')) {
            $output->info('Creating column password in table passwords_pw_tag_rel');
            $table->addColumn(
                'password',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('password_revision')) {
            $output->info('Creating column password_revision in table passwords_pw_tag_rel');
            $table->addColumn(
                'password_revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('tag')) {
            $output->info('Creating column tag in table passwords_pw_tag_rel');
            $table->addColumn(
                'tag',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('tag_revision')) {
            $output->info('Creating column tag_revision in table passwords_pw_tag_rel');
            $table->addColumn(
                'tag_revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('hidden')) {
            $output->info('Creating column hidden in table passwords_pw_tag_rel');
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_pw_tag_rel');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_pw_tag_rel');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_pw_tag_rel');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('client')) {
            $output->info('Creating column client in table passwords_pw_tag_rel');
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_pw_tag_rel');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_password_tag_index')) {
            $output->info('Removing index pw_password_tag_index from table passwords_pw_tag_rel');
            $table->dropIndex('pw_password_tag_index');
        }

        if(!$table->hasIndex('pw_index_password_tag')) {
            $output->info('Creating index pw_index_password_tag in table passwords_pw_tag_rel');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_password_tag');
        }

        if(!$table->hasIndex('pw_index_pw_tag_rel_ac')) {
            $output->info('Creating index pw_index_pw_tag_rel_ac in table passwords_pw_tag_rel');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_pw_tag_rel_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     * @throws Exception
     */
    protected function createShareTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_share')) {
            $output->info('Creating table passwords_share');
            $table = $schema->createTable('passwords_share');
        } else {
            $table = $schema->getTable('passwords_share');

            if($table->hasColumn('pwid')) {
                $qb = $this->db->getQueryBuilder();
                $delete = $qb
                    ->delete('passwords_share')
                    ->where($qb->expr()->isNotNull('pwid'));
                $delete->execute();

                $output->info('Removing column pwid from table passwords_share');
                $table->dropColumn('pwid');
            }

            if($table->hasColumn('sharedto')) {
                $output->info('Removing column sharedto from table passwords_share');
                $table->dropColumn('sharedto');
            }
            if($table->hasColumn('sharekey')) {
                $output->info('Removing column sharekey from table passwords_share');
                $table->dropColumn('sharekey');
            }
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_share');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_share');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_share');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('receiver')) {
            $output->info('Creating column receiver in table passwords_share');
            $table->addColumn(
                'receiver',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('source_password')) {
            $output->info('Creating column source_password in table passwords_share');
            $table->addColumn(
                'source_password',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('target_password')) {
            $output->info('Creating column target_password in table passwords_share');
            $table->addColumn(
                'target_password',
                'string',
                [
                    'notnull' => false,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('source_updated')) {
            $output->info('Creating column source_updated in table passwords_share');
            $table->addColumn(
                'source_updated',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('target_updated')) {
            $output->info('Creating column target_updated in table passwords_share');
            $table->addColumn(
                'target_updated',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('type')) {
            $output->info('Creating column type in table passwords_share');
            $table->addColumn(
                'type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('editable')) {
            $output->info('Creating column editable in table passwords_share');
            $table->addColumn(
                'editable',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('shareable')) {
            $output->info('Creating column shareable in table passwords_share');
            $table->addColumn(
                'shareable',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('expires')) {
            $output->info('Creating column expires in table passwords_share');
            $table->addColumn(
                'expires',
                'bigint',
                [
                    'notnull' => false,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_share');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_share');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_share');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('client')) {
            $output->info('Creating column client in table passwords_share');
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_share');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_share_index')) {
            $output->info('Removing index pw_share_index from table passwords_share');
            $table->dropIndex('pw_share_index');
        }

        if(!$table->hasIndex('pw_index_share')) {
            $output->info('Creating index pw_index_share in table passwords_share');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_share');
        }

        if(!$table->hasIndex('pw_index_share_ac')) {
            $output->info('Creating index pw_index_share_ac in table passwords_share');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_share_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createSessionTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_session')) {
            $output->info('Creating table passwords_session');
            $table = $schema->createTable('passwords_session');
        } else {
            $table = $schema->getTable('passwords_session');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_session');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_session');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_session');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('login_type')) {
            $output->info('Creating column login_type in table passwords_session');
            $table->addColumn(
                'login_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                    'default' => 'none',
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_session');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_session');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_session');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('authorized')) {
            $output->info('Creating column authorized in table passwords_session');
            $table->addColumn(
                'authorized',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasColumn('data')) {
            $output->info('Creating column data in table passwords_session');
            $table->addColumn(
                'data',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('shadow_data')) {
            $output->info('Creating column shadow_data in table passwords_session');
            $table->addColumn(
                'shadow_data',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('client')) {
            $output->info('Creating column client in table passwords_session');
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_session');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_session_index')) {
            $output->info('Removing index pw_session_index from table passwords_session');
            $table->dropIndex('pw_session_index');
        }

        if(!$table->hasIndex('pw_index_session')) {
            $output->info('Creating index pw_index_session in table passwords_session');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_session');
        }

        if(!$table->hasIndex('pw_index_session_ac')) {
            $output->info('Creating index pw_index_session_ac in table passwords_session');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_session_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createKeychainTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_keychain')) {
            $output->info('Creating table passwords_keychain');
            $table = $schema->createTable('passwords_keychain');
        } else {
            $table = $schema->getTable('passwords_keychain');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_keychain');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_keychain');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_keychain');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('type')) {
            $output->info('Creating column type in table passwords_keychain');
            $table->addColumn(
                'type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('scope')) {
            $output->info('Creating column scope in table passwords_keychain');
            $table->addColumn(
                'scope',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('data')) {
            $output->info('Creating column data in table passwords_keychain');
            $table->addColumn(
                'data',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_keychain');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_keychain');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_keychain');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_keychain');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_keychain_index')) {
            $output->info('Removing index pw_keychain_index from table passwords_keychain');
            $table->dropIndex('pw_keychain_index');
        }

        if(!$table->hasIndex('pw_index_keychain')) {
            $output->info('Creating index pw_index_keychain in table passwords_keychain');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_keychain');
        }

        if(!$table->hasIndex('pw_index_keychain_ac')) {
            $output->info('Creating index pw_index_keychain_ac in table passwords_keychain');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_keychain_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createChallengeTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_challenge')) {
            $output->info('Creating table passwords_challenge');
            $table = $schema->createTable('passwords_challenge');
        } else {
            $table = $schema->getTable('passwords_challenge');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_challenge');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_challenge');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_challenge');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('type')) {
            $output->info('Creating column type in table passwords_challenge');
            $table->addColumn(
                'type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('secret')) {
            $output->info('Creating column secret in table passwords_challenge');
            $table->addColumn(
                'secret',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('server_data')) {
            $output->info('Creating column server_data in table passwords_challenge');
            $table->addColumn(
                'server_data',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('client_data')) {
            $output->info('Creating column client_data in table passwords_challenge');
            $table->addColumn(
                'client_data',
                'text',
                [
                    'notnull' => false,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_challenge');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_challenge');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_challenge');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_challenge');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_challenge_index')) {
            $output->info('Removing index pw_challenge_index from table passwords_challenge');
            $table->dropIndex('pw_challenge_index');
        }

        if(!$table->hasIndex('pw_index_challenge')) {
            $output->info('Creating index pw_index_challenge in table passwords_challenge');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_challenge');
        }

        if(!$table->hasIndex('pw_index_challenge_ac')) {
            $output->info('Creating index pw_index_challenge_ac in table passwords_challenge');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_challenge_ac');
        }
    }

    /**
     * @param ISchemaWrapper $schema
     * @param IOutput        $output
     *
     * @throws SchemaException
     */
    protected function createRegistrationTable(ISchemaWrapper $schema, IOutput $output): void {
        if(!$schema->hasTable('passwords_registration')) {
            $output->info('Creating table passwords_registration');
            $table = $schema->createTable('passwords_registration');
        } else {
            $table = $schema->getTable('passwords_registration');
        }

        if(!$table->hasColumn('id')) {
            $output->info('Creating column id in table passwords_registration');
            $table->addColumn(
                'id',
                'bigint',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'length'        => 8,
                    'unsigned'      => true,
                ]
            );
        }

        if(!$table->hasColumn('user_id')) {
            $output->info('Creating column user_id in table passwords_registration');
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
        }

        if(!$table->hasColumn('uuid')) {
            $output->info('Creating column uuid in table passwords_registration');
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
        }

        if(!$table->hasColumn('label')) {
            $output->info('Creating column label in table passwords_registration');
            $table->addColumn(
                'label',
                'string',
                [
                    'notnull' => false,
                    'length'  => 256,
                ]
            );
        }

        if(!$table->hasColumn('code')) {
            $output->info('Creating column code in table passwords_registration');
            $table->addColumn(
                'code',
                'string',
                [
                    'notnull' => false,
                    'length'  => 128,
                ]
            );
        }

        if(!$table->hasColumn('login')) {
            $output->info('Creating column login in table passwords_registration');
            $table->addColumn(
                'login',
                'string',
                [
                    'notnull' => false,
                    'length'  => 128,
                ]
            );
        }

        if(!$table->hasColumn('token')) {
            $output->info('Creating column token in table passwords_registration');
            $table->addColumn(
                'token',
                'string',
                [
                    'notnull' => false,
                    'length'  => 128,
                ]
            );
        }

        if(!$table->hasColumn('limit')) {
            $output->info('Creating column limit in table passwords_registration');
            $table->addColumn(
                'limit',
                'bigint',
                [
                    'notnull' => false,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('status')) {
            $output->info('Creating column status in table passwords_registration');
            $table->addColumn(
                'status',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                    'default' => 0,
                ]
            );
        }

        if(!$table->hasColumn('created')) {
            $output->info('Creating column created in table passwords_registration');
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('updated')) {
            $output->info('Creating column updated in table passwords_registration');
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
        }

        if(!$table->hasColumn('deleted')) {
            $output->info('Creating column deleted in table passwords_registration');
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
        }

        if(!$table->hasPrimaryKey()) {
            $output->info('Creating primary key id in table passwords_registration');
            $table->setPrimaryKey(['id']);
        }

        if($table->hasIndex('pw_registration_index')) {
            $output->info('Removing index pw_registration_index from table passwords_registration');
            $table->dropIndex('pw_registration_index');
        }

        if(!$table->hasIndex('pw_index_registration')) {
            $output->info('Creating index pw_index_registration in table passwords_registration');
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_registration');
        }

        if(!$table->hasIndex('pw_index_registration_ac')) {
            $output->info('Creating index pw_index_registration_ac in table passwords_registration');
            $table->addUniqueIndex(['user_id', 'uuid', 'deleted'], 'pw_index_registration_ac');
        }
    }
}
