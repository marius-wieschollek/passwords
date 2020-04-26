<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class AbstractMapper
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractMapper extends QBMapper {

    const TABLE_NAME        = 'passwords';
    const ALLOWED_OPERATORS = ['eq', 'neq', 'lt', 'gt', 'lte', 'gte'];
    const FORBIDDEN_FIELDS    = [];

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var array
     */
    protected $entityCache = [];

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
     * @param Entity|EntityInterface $entity
     *
     * @return Entity|EntityInterface
     */
    public function delete(Entity $entity): Entity {
        if(isset($this->entityCache[ $entity->getUuid() ])) {
            unset($this->entityCache[ $entity->getUuid() ]);
        }

        return parent::delete($entity);
    }

    /**
     * @param Entity|EntityInterface $entity
     *
     * @return Entity|EntityInterface
     */
    public function insert(Entity $entity): Entity {
        $this->entityCache[ $entity->getUuid() ] = $entity;

        return parent::insert($entity);
    }

    /**
     * @param Entity|EntityInterface $entity
     *
     * @return Entity|EntityInterface
     */
    public function update(Entity $entity): Entity {
        $this->entityCache[ $entity->getUuid() ] = $entity;

        return parent::update($entity);
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
        if(isset($this->entityCache[ $uuid ])) {
            return $this->entityCache[ $uuid ];
        }

        return $this->findOneByField('uuid', $uuid);
    }

    /**
     * @param $search
     *
     * @return EntityInterface|Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @deprecated
     */
    public function findOneByIdOrUuid($search): EntityInterface {
        $sql = $this->getStatement();

        $sql->andWhere(
            $sql->expr()->orX(
                $sql->expr()->eq('id', $sql->createNamedParameter($search, IQueryBuilder::PARAM_INT)),
                $sql->expr()->eq('uuid', $sql->createNamedParameter($search))
            )
        );

        return $this->findEntity($sql);
    }

    /**
     * @param string $userId
     *
     * @return EntityInterface[]
     * @throws \Exception
     */
    public function findAllByUserId(string $userId): array {
        return $this->findAllByField('user_id', $userId);
    }

    /**
     * @return EntityInterface[]
     */
    public function findAllDeleted(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from(static::TABLE_NAME)
           ->where(
               $qb->expr()->eq('deleted', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
           );

        if($this->userId !== null) {
            $qb->andWhere(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId))
            );
        }

        return $this->findEntities($qb);
    }

    /**
     * @return EntityInterface[]
     */
    public function findAll(): array {
        $sql = $this->getStatement();

        return $this->findEntities($sql);
    }

    /**
     * @param string $field
     * @param string $value
     * @param int    $type
     * @param string $operator
     *
     * @return EntityInterface|Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findOneByField(string $field, string $value, $type = IQueryBuilder::PARAM_STR, string $operator = 'eq'): EntityInterface {
        return $this->findOneByFields([$field, $value, $type, $operator]);
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param int    $type
     * @param string $operator
     *
     * @return EntityInterface[]
     * @throws \Exception
     */
    public function findAllByField(string $field, $value, $type = IQueryBuilder::PARAM_STR, string $operator = 'eq'): array {
        return $this->findAllByFields([$field, $value, $type, $operator]);
    }

    /**
     * @param array ...$fields
     *
     * @return EntityInterface|Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function findOneByFields(array ...$fields): EntityInterface {
        $sql = $this->buildQuery($fields);

        return $this->findEntity($sql);
    }

    /**
     * @param array ...$fields
     *
     * @return array|Entity[]
     * @throws \Exception
     */
    public function findAllByFields(array ...$fields): array {
        $sql = $this->buildQuery($fields);

        return $this->findEntities($sql);
    }

    /**
     *
     */
    public function clearEntityCache(): void {
        $this->entityCache = [];
    }

    /**
     * @return IQueryBuilder
     */
    protected function getStatement(): IQueryBuilder {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from(static::TABLE_NAME)
           ->where(
               $qb->expr()->eq('deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
           );

        if($this->userId !== null) {
            $qb->andWhere(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId))
            );
        }

        return $qb;
    }

    /**
     * @param string $toTable
     * @param string $fromField
     * @param string $toField
     *
     * @return IQueryBuilder
     */
    protected function getJoinStatement(string $toTable, string $fromField = 'revision', string $toField = 'uuid'): IQueryBuilder {
        $sql = $this->db->getQueryBuilder();

        $sql->select('a.*')
            ->from(static::TABLE_NAME, 'a')
            ->innerJoin('a', $toTable, 'b', "a.`{$fromField}` = b.`{$toField}`")
            ->where(
                $sql->expr()->eq('a.deleted', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
            );

        if($this->userId !== null) {
            $sql->andWhere(
                $sql->expr()->eq('a.user_id', $sql->createNamedParameter($this->userId)),
                $sql->expr()->eq('b.user_id', $sql->createNamedParameter($this->userId))
            );
        }

        return $sql;
    }

    /**
     * @param array $fields
     *
     * @return IQueryBuilder
     * @throws \Exception
     */
    protected function buildQuery(array $fields): IQueryBuilder {
        $sql = $this->getStatement();

        foreach($fields as $field) {
            if(!isset($field[0])) throw new \Exception('Field name is required but not set');
            $name  = $field[0];
            $value = isset($field[1]) ? $field[1]:'';
            $type  = isset($field[2]) ? $field[2]:IQueryBuilder::PARAM_STR;
            $op    = isset($field[3]) ? $field[3]:'eq';

            if(in_array($name, static::FORBIDDEN_FIELDS)) throw new \Exception('Forbidden field in database query');
            if(!in_array($op, self::ALLOWED_OPERATORS)) throw new \Exception('Forbidden operator in database query');

            if($type !== IQueryBuilder::PARAM_NULL && $value !== null) {
                $sql->andWhere(
                    $sql->expr()->{$op}($name, $sql->createNamedParameter($value, $type))
                );
            } else {
                $op = $op === 'eq' ? 'isNull':'isNotNull';
                $sql->andWhere($sql->expr()->{$op}($name));
            }
        }

        return $sql;
    }

    /**
     * @param array $row
     *
     * @return Entity
     */
    protected function mapRowToEntity(array $row): Entity {
        if(isset($row['uuid']) && isset($this->entityCache[ $row['uuid'] ])) {
            return $this->entityCache[ $row['uuid'] ];
        }

        $entity = parent::mapRowToEntity($row);
        if(isset($row['uuid'])) {
            $this->entityCache[ $row['uuid'] ] = $entity;
        }

        return $entity;
    }
}