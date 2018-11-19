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

    const TABLE_NAME = 'passwords';

    /**
     * @var string
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
     * @param int $id
     *
     * @return EntityInterface|Entity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findById(int $id): EntityInterface {
        return $this->findOneByField('id', $id, IQueryBuilder::PARAM_INT);
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
                $sql->expr()->eq('uuid', $sql->createNamedParameter($search, IQueryBuilder::PARAM_STR))
            )
        );

        return $this->findEntity($sql);
    }

    /**
     * @param string $userId
     *
     * @return EntityInterface[]
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
                $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId, IQueryBuilder::PARAM_STR))
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
     *
     * @return EntityInterface|Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findOneByField(string $field, string $value, $type = IQueryBuilder::PARAM_STR): EntityInterface {
        $sql = $this->getStatement();

        $sql->andWhere(
            $sql->expr()->eq($field, $sql->createNamedParameter($value, $type))
        );

        return $this->findEntity($sql);
    }

    /**
     * @param string $field
     * @param string $value
     * @param int    $type
     *
     * @return EntityInterface[]
     */
    public function findAllByField(string $field, string $value, $type = IQueryBuilder::PARAM_STR): array {
        $sql = $this->getStatement();

        $sql->andWhere(
            $sql->expr()->eq($field, $sql->createNamedParameter($value, $type))
        );

        return $this->findEntities($sql);
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
                $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId, IQueryBuilder::PARAM_STR))
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
                $sql->expr()->eq('a.user_id', $sql->createNamedParameter($this->userId, IQueryBuilder::PARAM_STR)),
                $sql->expr()->eq('b.user_id', $sql->createNamedParameter($this->userId, IQueryBuilder::PARAM_STR))
            );
        }

        return $sql;
    }

    /**
     * @param Entity $entity
     *
     * @return Entity
     */
    public function insert(Entity $entity): Entity {
        // get updated fields to save, fields have to be set using a setter to
        // be saved
        $properties = $entity->getUpdatedFields();

        $qb = $this->db->getQueryBuilder();
        $qb->insert($this->tableName);
        $types = $entity->getFieldTypes();

        // build the fields
        foreach($properties as $property => $updated) {
            $column = $entity->propertyToColumn($property);
            $getter = 'get' . ucfirst($property);
            $value = $entity->$getter();

            if(isset($types[$property]) && $types[$property] === 'boolean') {
                $qb->setValue($column, $qb->createNamedParameter($value, IQueryBuilder::PARAM_BOOL));
            } else {
                $qb->setValue($column, $qb->createNamedParameter($value));
            }
        }

        $qb->execute();

        $entity->setId((int) $qb->getLastInsertId());

        return $entity;
    }

    /**
     * @param Entity $entity
     *
     * @return Entity
     */
    public function update(Entity $entity): Entity {
        // if entity wasn't changed it makes no sense to run a db query
        $properties = $entity->getUpdatedFields();
        if(\count($properties) === 0) {
            return $entity;
        }

        // entity needs an id
        $id = $entity->getId();
        if($id === null){
            throw new \InvalidArgumentException(
                'Entity which should be updated has no id');
        }

        // get updated fields to save, fields have to be set using a setter to
        // be saved
        // do not update the id field
        unset($properties['id']);

        $qb = $this->db->getQueryBuilder();
        $qb->update($this->tableName);
        $types = $entity->getFieldTypes();

        // build the fields
        foreach($properties as $property => $updated) {
            $column = $entity->propertyToColumn($property);
            $getter = 'get' . ucfirst($property);
            $value = $entity->$getter();

            if(isset($types[$property]) && $types[$property] === 'boolean') {
                $qb->set($column, $qb->createNamedParameter($value, IQueryBuilder::PARAM_BOOL));
            } else {
                $qb->set($column, $qb->createNamedParameter($value));
            }
        }

        $qb->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($id))
        );
        $qb->execute();

        return $entity;
    }
}