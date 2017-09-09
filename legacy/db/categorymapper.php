<?php

namespace OCA\Passwords\Db;

use OCA\Passwords\Helper\DatabaseHelper;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

/**
 * Class CategoryMapper
 *
 * @package OCA\Passwords\Db
 */
class CategoryMapper extends Mapper {

    /**
     * @var DatabaseHelper
     */
    private $databaseHelper;

    /**
     * CategoryMapper constructor.
     *
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        $this->databaseHelper = new DatabaseHelper();

        parent::__construct($db, 'passwords_categories', '\OCA\Passwords\Db\Category');
    }

    /**
     * @param $id
     * @param $userId
     *
     * @return \OCP\AppFramework\Db\Entity
     */
    public function find($id, $userId) {
        $sql = 'SELECT * FROM *PREFIX*passwords_categories WHERE id = ? AND user_id = ?';

        return $this->findEntity($sql, [$id, $userId]);
    }

    /**
     * @param $userId
     *
     * @return array
     */
    public function findAll($userId) {
        $sql = 'SELECT * FROM *PREFIX*passwords_categories WHERE user_id = ? ORDER BY '.
               $this->databaseHelper->getDatabaseDependentStatement([
                   'mysql'   => "LOWER(category_name) COLLATE {$this->databaseHelper->getCollation()} ASC",
                   'sqlite'  => 'category_name COLLATE NOCASE',
                   'default' => 'LOWER(category_name) ASC'
               ]);

        return $this->findEntities($sql, [$userId]);
    }
}
