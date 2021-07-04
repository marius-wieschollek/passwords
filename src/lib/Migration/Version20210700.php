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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version20210700 extends SimpleMigrationStep {

    /**
     * @var IDBConnection
     */
    protected IDBConnection $db;

    /**
     * Version20210700 constructor.
     *
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        $this->db = $db;
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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if(!$schema->hasTable('passwords_password')) {
            $output->info('Creating table passwords_password');
            $table = $schema->createTable('passwords_password');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'share_id',
                'string',
                [
                    'notnull' => false,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'has_shares',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'editable',
                'boolean',
                [
                    'notnull' => false,
                    'default' => true,
                ]
            );
            $table->addColumn(
                'suspended',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_password');
        }

        if(!$schema->hasTable('passwords_password_rv')) {
            $output->info('Creating table passwords_password_rv');
            $table = $schema->createTable('passwords_password_rv');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'model',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'folder',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'trashed',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'cse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
            $table->addColumn(
                'cse_key',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
            $table->addColumn(
                'sse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
            $table->addColumn(
                'sse_key',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'username',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'password',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'label',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'url',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'notes',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'custom_fields',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'hash',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'favorite',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'status',
                'smallint',
                [
                    'notnull' => true,
                    'length'  => 1,
                    'default' => 0,
                ]
            );
            $table->addColumn(
                'status_code',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                    'default' => 'GOOD',
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'edited',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_password_revision');
        }

        if(!$schema->hasTable('passwords_folder')) {
            $output->info('Creating table passwords_folder');
            $table = $schema->createTable('passwords_folder');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'suspended',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_folder');
        }

        if(!$schema->hasTable('passwords_folder_rv')) {
            $output->info('Creating table passwords_folder_rv');
            $table = $schema->createTable('passwords_folder_rv');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'model',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'parent',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'label',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'cse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
            $table->addColumn(
                'cse_key',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
            $table->addColumn(
                'sse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
            $table->addColumn(
                'sse_key',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'favorite',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'trashed',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'edited',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_folder_revision');
        }

        if(!$schema->hasTable('passwords_tag')) {
            $output->info('Creating table passwords_tag');
            $table = $schema->createTable('passwords_tag');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_tag');
        }

        if(!$schema->hasTable('passwords_tag_rv')) {
            $output->info('Creating table passwords_tag_rv');
            $table = $schema->createTable('passwords_tag_rv');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'model',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'cse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
            $table->addColumn(
                'cse_key',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
            $table->addColumn(
                'sse_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 10,
                ]
            );
            $table->addColumn(
                'sse_key',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'label',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'color',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'favorite',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'trashed',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'edited',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'uuid'], 'pw_index_tag_revision');
        }

        if(!$schema->hasTable('passwords_pw_tag_rel')) {
            $output->info('Creating table passwords_pw_tag_rel');
            $table = $schema->createTable('passwords_pw_tag_rel');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                    'default' => '',
                ]
            );
            $table->addColumn(
                'password',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'password_revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'tag',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'tag_revision',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'hidden',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addIndex(['tag', 'password'], 'pw_index_password_tag');
        }

        if(!$schema->hasTable('passwords_share')) {
            $output->info('Creating table passwords_share');
            $table = $schema->createTable('passwords_share');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'receiver',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'source_password',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'target_password',
                'string',
                [
                    'notnull' => false,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'source_updated',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'target_updated',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'editable',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'shareable',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'expires',
                'bigint',
                [
                    'notnull' => false,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uuid', 'user_id', 'receiver'], 'pw_index_share');
        }

        if(!$schema->hasTable('passwords_session')) {
            $output->info('Creating table passwords_session');
            $table = $schema->createTable('passwords_session');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'login_type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                    'default' => 'none',
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'authorized',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'data',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'shadow_data',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'client',
                'string',
                [
                    'notnull' => true,
                    'length'  => 256,
                    'default' => 'CLIENT::UNKNOWN',
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uuid', 'user_id'], 'pw_index_session');
        }

        if(!$schema->hasTable('passwords_keychain')) {
            $output->info('Creating table passwords_keychain');
            $table = $schema->createTable('passwords_keychain');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'scope',
                'string',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'data',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uuid', 'user_id'], 'pw_index_keychain');
        }

        if(!$schema->hasTable('passwords_challenge')) {
            $output->info('Creating table passwords_challenge');
            $table = $schema->createTable('passwords_challenge');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'type',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'secret',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'server_data',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'client_data',
                'text',
                [
                    'notnull' => false,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uuid', 'user_id'], 'pw_index_challenge');
        }

        if(!$schema->hasTable('passwords_registration')) {
            $output->info('Creating table passwords_registration');
            $table = $schema->createTable('passwords_registration');
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
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => true,
                    'length'  => 64,
                ]
            );
            $table->addColumn(
                'uuid',
                'string',
                [
                    'notnull' => true,
                    'length'  => 36,
                ]
            );
            $table->addColumn(
                'label',
                'string',
                [
                    'notnull' => false,
                    'length'  => 256,
                ]
            );
            $table->addColumn(
                'code',
                'string',
                [
                    'notnull' => false,
                    'length'  => 128,
                ]
            );
            $table->addColumn(
                'login',
                'string',
                [
                    'notnull' => false,
                    'length'  => 128,
                ]
            );
            $table->addColumn(
                'token',
                'string',
                [
                    'notnull' => false,
                    'length'  => 128,
                ]
            );
            $table->addColumn(
                'limit',
                'bigint',
                [
                    'notnull' => false,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'status',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                    'default' => 0,
                ]
            );
            $table->addColumn(
                'created',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'updated',
                'bigint',
                [
                    'notnull' => true,
                    'length'  => 12,
                ]
            );
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uuid', 'user_id'], 'pw_index_registration');
        }

        return $schema;
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
                $select = $this->db->getQueryBuilder()->select('a.*')->from($oldTable, 'a');
                $uuids  = $this->getMigratedUuids($newTable);

                /**
                 * @TODO Remove execute() in 2022.1.0
                 */
                if(method_exists($select, 'executeQuery')) {
                    $result = $select->executeQuery();
                } else {
                    $result = $select->execute();
                }

                $total = $result->rowCount();
                $items = $result->fetchAll();

                $output->info("Migrating {$total} entries from {$oldTable} to {$newTable}");
                $output->startProgress($total);
                foreach($items as $item) {
                    $query = $this->db->getQueryBuilder()->insert($newTable);
                    if(in_array($item['uuid'], $uuids)) {
                        $output->info("Skipping {$item['uuid']} because it exists in new table");
                        $output->advance($total);
                        continue;
                    }

                    foreach($item as $key => $value) {
                        if($key === 'id') continue;
                        $type = $schema->getTable($newTable)->getColumn($key)->getType();
                        $query->setValue($key, $query->createNamedParameter($value, $type));
                    }

                    /**
                     * @TODO Remove execute() in 2022.1.0
                     */
                    if(method_exists($query, 'executeStatement')) {
                        $query->executeStatement();
                    } else {
                        $query->execute();
                    }
                    $output->advance($total);
                }
                $output->finishProgress();
                $output->info('Done');
            }
        }
    }

    /**
     * @param string                             $newTable
     * @param \OCP\DB\QueryBuilder\IQueryBuilder $select
     *
     * @return array
     * @throws \OCP\DB\Exception
     */
    protected function getMigratedUuids(string $newTable): array {
        $select = $this->db->getQueryBuilder()->select('a.uuid')->from($newTable, 'a');
        /**
         * @TODO Remove execute() in 2022.1.0
         */
        if(method_exists($select, 'executeQuery')) {
            $result = $select->executeQuery();
        } else {
            $result = $select->execute();
        }
        $items = $result->fetchAll();

        $uuids = [];
        foreach($items as $item) {
            $uuids[] = $item['uuid'];
        }

        return $uuids;
    }
}
