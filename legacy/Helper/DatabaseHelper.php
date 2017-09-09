<?php

namespace OCA\Passwords\Helper;

/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.06.17
 * Time: 22:47
 */
class DatabaseHelper {

    /**
     * @var \OCP\IConfig
     */
    protected $config;

    public function __construct() {
        $this->config = \OC::$server->getConfig();
    }

    /**
     * @return string
     */
    public function getCollation(): string {
        $isUtf8Mb4Enabled = $this->config->getSystemValue('mysql.utf8mb4', false);

        return $isUtf8Mb4Enabled ? 'utf8mb4_general_ci':'utf8_general_ci';
    }

    /**
     * @return string
     */
    public function getDatabaseType(): string {
        $dbtype = $this->config->getSystemValue('dbtype', 'sqlite');

        if($dbtype == 'sqlite3') return 'sqlite';

        return $dbtype;
    }

    /**
     * @param array $statements
     *
     * @return null|string
     */
    public function getDatabaseDependentStatement(array $statements): ?string {
        $dbtype = $this->getDatabaseType();

        return isset($statements[ $dbtype ]) ? $statements[ $dbtype ]:$statements['default'];
    }
}