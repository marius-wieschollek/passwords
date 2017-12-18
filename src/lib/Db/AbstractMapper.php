<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 22:47
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;
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

    protected $allowedFields       = ['id', 'uuid'];
    protected $logicalOperators    = ['AND', 'OR'];
    protected $comparisonOperators = ['=', '!=', '<', '>'];

    /**
     * AbstractMapper constructor.
     *
     * @param IDBConnection $db
     * @param string        $userId
     */
    public function __construct(IDBConnection $db, $userId) {
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
     * @return AbstractEntity|Entity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findById(int $id): AbstractEntity {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `id` = ?';
        $params[] = $id;

        return $this->findEntity($sql, $params);
    }

    /**
     * @param string $uuid
     *
     * @return AbstractEntity|Entity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): AbstractEntity {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `uuid` = ?';
        $params[] = $uuid;

        return $this->findEntity($sql, $params);
    }

    /**
     * @return AbstractEntity[]
     */
    public function findAll(): array {
        list($sql, $params) = $this->getStatement();

        return $this->findEntities($sql, $params);
    }

    /**
     * @param array $search
     *
     * @return AbstractEntity[]
     * @throws \Exception
     */
    public function findAllMatching(array $search): array {
        return $this->findMatching($search);
    }

    /**
     * @param array $search
     *
     * @return null|AbstractEntity
     * @throws \Exception
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
     * @throws \Exception
     */
    public function findMatching(array $search = [], int $limit = null): array {
        if(isset($search[0]) && !is_array($search[0])) $search = [$search];
        list($sql, $params) = $this->getStatement();

        foreach ($search as $criteria) {
            if(!isset($criteria[2]) || !in_array($criteria[2], $this->comparisonOperators)) $criteria[2] = '=';
            if(!isset($criteria[3]) || !in_array($criteria[3], $this->logicalOperators)) $criteria[3] = 'AND';

            list($field, $value, $operator, $concat) = $criteria;
            if(!in_array($field, $this->allowedFields)) {
                throw new \Exception('Illegal field `'.static::TABLE_NAME.'`.`'.$field.'` in database request');
            }

            $sql      .= ' '.$concat.' `'.$field.'` '.$operator.' ? ';
            $params[] = $value;
        }

        return $this->findEntities($sql, $params, $limit);
    }

    /**
     * @return array
     */
    protected function getStatement(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE `deleted` = 0 ';

        $params = [];
        if($this->userId != '') {
            $sql      .= ' AND `user_id` = ?';
            $params[] = $this->userId;
        }

        return [$sql, $params];
    }
}