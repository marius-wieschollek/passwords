<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db\Legacy;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

/**
 * Class LegacyPasswordMapper
 *
 * @package OCA\Passwords\Db\Legacy
 */
class LegacyPasswordMapper extends Mapper {

    const TABLE_NAME = 'passwords';

    /**
     * LegacyPasswordMapper constructor.
     *
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        parent::__construct($db, static::TABLE_NAME);
    }

    /**
     * @return LegacyPassword[]
     */
    public function findAll(): array {
        list($sql, $params) = $this->getStatement();

        return $this->findEntities($sql, $params);
    }

    /**
     * @return array
     */
    protected function getStatement(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE `deleted` = ?';

        return [$sql, [false]];
    }
}