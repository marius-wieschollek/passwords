<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 22:47
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

/**
 * Class AbstractMapper
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractMapper extends Mapper {

    const TABLE_NAME = 'passwords';

    /**
     * @var string
     */
    protected $userId;

    /**
     * AbstractMapper constructor.
     *
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db, string $userId) {
        parent::__construct($db, static::TABLE_NAME);
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function generateUuidV4() {
        return implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)).bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)).bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6))
        ]);
    }

    /**
     * @param int $id
     *
     * @return AbstractEntity
     */
    public function findById(int $id): AbstractEntity {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` '.
               'WHERE `id` = ? AND `user` = ? AND `deleted` = 0 ';

        return $this->findEntity($sql, [$id, $this->userId]);
    }

    /**
     * @param string $id
     *
     * @return AbstractEntity
     */
    public function findByUuid(string $id): AbstractEntity {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` '.
               'WHERE `uuid` = ? AND `user` = ? AND `deleted` = 0 ';

        return $this->findEntity($sql, [$id, $this->userId]);
    }

    /**
     * @return AbstractEntity[]
     */
    public function findAll(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'`'.
               'WHERE `user` = ? AND `deleted` = 0 ';

        return $this->findEntities($sql, [$this->userId]);
    }

    /**
     * @param array $search
     *
     * @return AbstractEntity[]
     */
    public function findAllMatching(array $search): array {
        return $this->findMatching($search);
    }

    /**
     * @param array $search
     *
     * @return null|AbstractEntity
     */
    public function findOneMatching(array $search): ?AbstractEntity {
        $matches = $this->findMatching($search, 1);

        if(isset($matches[0])) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @param array    $search
     * @param int|null $limit
     *
     * @return AbstractEntity[]
     */
    public function findMatching(array $search, int $limit = null): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'`'.
               'WHERE `user` = ? AND `deleted` = 0 ';

        $params = [$this->userId];
        foreach ($search as $key => $value) {
            $sql      .= ' AND `'.$key.'` = ? ';
            $params[] = $value;
        }

        return $this->findEntities($sql, $params, $limit);
    }
}