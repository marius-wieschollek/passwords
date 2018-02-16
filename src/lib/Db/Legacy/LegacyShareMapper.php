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
 * Class LegacyShareMapper
 *
 * @package OCA\Passwords\Db\Legacy
 */
class LegacyShareMapper extends Mapper {

    const TABLE_NAME = 'passwords_share';

    /**
     * LegacyShareMapper constructor.
     *
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        parent::__construct($db, static::TABLE_NAME);
    }

    /**
     * @return LegacyShare[]
     */
    public function findAll(): array {
        return $this->findEntities($this->getStatement());
    }

    /**
     * @return string
     */
    protected function getStatement(): string {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE sharedto != \'\'';

        return $sql;
    }
}