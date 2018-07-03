<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCA\Passwords\Services\EnvironmentService;
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

    /**
     * @var array
     */
    protected $allowedFields = ['id', 'uuid'];

    /**
     * @var array
     */
    protected $logicalOperators = ['AND', 'OR'];

    /**
     * @var array
     */
    protected $comparisonOperators = ['=', '!=', '<', '>'];

    /**
     * AbstractMapper constructor.
     *
     * @param IDBConnection      $db
     * @param EnvironmentService $environment
     */
    public function __construct(IDBConnection $db, EnvironmentService $environment) {
        parent::__construct($db, static::TABLE_NAME);
        $this->userId = $environment->getUserId();
    }

    /**
     * @return string
     * @deprecated
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
     * @return EntityInterface|Entity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findById(int $id): EntityInterface {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `id` = ?';
        $params[] = $id;

        return $this->findEntity($sql, $params);
    }

    /**
     * @param string $uuid
     *
     * @return EntityInterface|Entity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): EntityInterface {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `uuid` = ?';
        $params[] = $uuid;

        return $this->findEntity($sql, $params);
    }

    /**
     * @param string $userId
     *
     * @return EntityInterface[]
     */
    public function findByUserId(string $userId): array {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `user_id` = ?';
        $params[] = $userId;

        return $this->findEntities($sql, $params);
    }

    /**
     * @return EntityInterface[]
     */
    public function findDeleted(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE `deleted` = ?';

        $params = [true];
        if($this->userId !== null) {
            $sql      .= ' AND `*PREFIX*'.static::TABLE_NAME.'`.`user_id` = ?';
            $params[] = $this->userId;
        }

        return $this->findEntities($sql, $params);
    }

    /**
     * @return EntityInterface[]
     */
    public function findAll(): array {
        list($sql, $params) = $this->getStatement();

        return $this->findEntities($sql, $params);
    }

    /**
     * @param array $search
     *
     * @return EntityInterface[]
     * @throws \Exception
     */
    public function findAllMatching(array $search): array {
        return $this->findMatching($search);
    }

    /**
     * @param array $search
     *
     * @return null|EntityInterface
     * @throws \Exception
     */
    public function findOneMatching(array $search): ?EntityInterface {
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
     * @return EntityInterface[]
     * @throws \Exception
     */
    public function findMatching(array $search = [], int $limit = null): array {
        if(isset($search[0]) && !is_array($search[0])) $search = [$search];
        list($sql, $params) = $this->getStatement();

        $extraSql = '';
        $concat   = '';
        foreach($search as $criteria) {
            list($field, $value, $operator, $nextConcat) = $this->processCriteria($criteria);

            if($value !== null) {
                $extraSql .= "{$concat} `*PREFIX*".static::TABLE_NAME."`.`{$field}` {$operator} ? ";
                $params[] = $value;
            } else if($operator === '!=') {
                $extraSql .= "{$concat} `*PREFIX*".static::TABLE_NAME."`.`{$field}` IS NOT NULL ";
            } else {
                $extraSql .= "{$concat} `*PREFIX*".static::TABLE_NAME."`.`{$field}` IS NULL ";
            }
            $concat = $nextConcat;
        }
        if($extraSql) $sql .= " AND ($extraSql)";

        return $this->findEntities($sql, $params, $limit);
    }

    /**
     * @return array
     */
    protected function getStatement(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE `deleted` = ?';

        $params = [false];
        if($this->userId !== null) {
            $sql      .= ' AND `*PREFIX*'.static::TABLE_NAME.'`.`user_id` = ?';
            $params[] = $this->userId;
        }

        return [$sql, $params];
    }

    /**
     * @param $criteria
     *
     * @return mixed
     * @throws \Exception
     */
    protected function processCriteria(array $criteria): array {
        if(!isset($criteria[2]) || !in_array($criteria[2], $this->comparisonOperators)) $criteria[2] = '=';
        if(!isset($criteria[3]) || !in_array($criteria[3], $this->logicalOperators)) $criteria[3] = 'AND';

        if(!in_array($criteria[0], $this->allowedFields)) {
            throw new \Exception('Illegal field `'.static::TABLE_NAME.'`.`'.$criteria[0].'` in database request');
        }

        return $criteria;
    }
}