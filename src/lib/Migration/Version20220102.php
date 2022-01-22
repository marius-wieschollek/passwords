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
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Class Version20220102
 *
 * @package OCA\Passwords\Migration
 */
class Version20220102 extends SimpleMigrationStep {

    /**
     * @var IDBConnection
     */
    protected IDBConnection $db;

    /**
     * @var IConfig
     */
    protected IConfig $config;

    /**
     * Version20220102 constructor.
     *
     * @param IDBConnection $db
     * @param IConfig       $config
     */
    public function __construct(IDBConnection $db, IConfig $config) {
        $this->db     = $db;
        $this->config = $config;
    }

    /**
     * Human readable name of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function name(): string {
        return 'Update auto_increment';
    }

    /**
     * Human readable description of the migration step
     *
     * @return string
     * @since 14.0.0
     */
    public function description(): string {
        return 'Update auto_increment values of postgres tables';
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
     * @throws \Doctrine\DBAL\Exception
     * @throws \OCP\DB\Exception
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
        if($this->config->getSystemValue('dbtype') !== 'pgsql') {
            return;
        }

        $tableMap = [
            'passwords_challenge'    => 'passwords_challenge_id_seq',
            'passwords_folder'       => 'passwords_folder_id_seq',
            'passwords_folder_rv'    => 'passwords_folder_rv_id_seq',
            'passwords_keychain'     => 'passwords_keychain_id_seq',
            'passwords_password'     => 'passwords_password_id_seq',
            'passwords_password_rv'  => 'passwords_password_rv_id_seq',
            'passwords_registration' => 'passwords_registration_id_seq',
            'passwords_session'      => 'passwords_session_id_seq',
            'passwords_share'        => 'passwords_share_id_seq',
            'passwords_tag'          => 'passwords_tag_id_seq',
            'passwords_tag_rv'       => 'passwords_tag_rv_id_seq',
            'passwords_pw_tag_rel'   => 'passwords_pw_tag_rel_id_seq'
        ];

        foreach($tableMap as $table => $sequence) {
            $queryBuilder = $this->db->getQueryBuilder();
            $select       = $queryBuilder->selectAlias($queryBuilder->createFunction('MAX(`a`.`id`)'), 'count')->from($table, 'a');

            $result = $select->executeQuery();
            $count = $result->fetch()['count'];
            if($count === null) {
                $output->info("Table {$table} seems to be empty, not updating auto_increment");
                continue;
            }

            $count++;
            $output->info("Setting auto_increment for {$sequence} to {$count}");
            $statement = $this->db->prepare("ALTER SEQUENCE *PREFIX*{$sequence} RESTART WITH {$count}");
            $statement->execute();
        }
        $output->info('Done');
    }
}
