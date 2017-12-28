<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 27.12.17
 * Time: 23:27
 */

namespace OCA\Passwords\Db\Legacy;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

/**
 * Class LegacyCategoryMapper
 *
 * @package OCA\Passwords\Db\Legacy
 */
class LegacyCategoryMapper extends Mapper {

    const TABLE_NAME = 'passwords_categories';

    /**
     * LegacyCategoryMapper constructor.
     *
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        parent::__construct($db, static::TABLE_NAME);
    }

    /**
     * @return LegacyCategory[]
     */
    public function findAll(): array {
        return $this->findEntities('SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'`');
    }
}