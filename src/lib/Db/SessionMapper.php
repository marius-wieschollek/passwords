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
 * Class SessionMapper
 *
 * @package OCA\Passwords\Db
 */
class SessionMapper extends Mapper {

    const TABLE_NAME = 'passwords_entity_session';

    /**
     * @var null|string
     */
    protected $userId;

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
     * @param string $uuid
     *
     * @return Session|Entity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): Session {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `uuid` = ?';
        $params[] = $uuid;

        return $this->findEntity($sql, $params);
    }

    /**
     * @param int $updated
     *
     * @return array
     */
    public function findAllOlderThan(int $updated): array {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `updated` <= ?';
        $params[] = $updated;

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
     * @return array
     */
    protected function getStatement(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE ';

        $params = [];
        if($this->userId !== null) {
            $sql      .= ' `*PREFIX*'.static::TABLE_NAME.'`.`user_id` = ?';
            $params[] = $this->userId;
        } else {
            $sql .= ' `*PREFIX*'.static::TABLE_NAME.'`.`id` != 0';
        }

        return [$sql, $params];
    }
}