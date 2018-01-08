<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.12.17
 * Time: 23:28
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