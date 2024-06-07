<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Db;

use OCA\Passwords\Exception\Database\DecryptedDataException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AbstractRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractRevisionMapper extends AbstractMapper {

    const MODEL_TABLE_NAME = '';


    /**
     * @return EntityInterface[]
     * @throws Exception
     */
    public function findAllHidden(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from(static::TABLE_NAME)
           ->where(
               $qb->expr()->eq('deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)),
               $qb->expr()->eq('hidden', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
           );

        if($this->userId !== null) {
            $qb->andWhere(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId))
            );
        }

        return $this->findEntities($qb);
    }

    /**
     * @param string $modelUuid
     *
     * @return RevisionInterface|null
     * @throws DoesNotExistException
     * @throws Exception
     * @throws MultipleObjectsReturnedException
     */
    public function findCurrentRevisionByModel(string $modelUuid): ?RevisionInterface {
        $sql = $this->getJoinStatement(static::MODEL_TABLE_NAME, 'model');

        $sql->andWhere(
            $sql->expr()->eq('b.user_id', 'a.user_id'),
            $sql->expr()->eq('b.revision', 'a.uuid'),
            $sql->expr()->eq('b.uuid', $sql->createNamedParameter($modelUuid))
        );

        return $this->findEntity($sql);
    }

    /**
     * @param string $modelUuid
     *
     * @return RevisionInterface[]|Entity[]
     * @throws Exception
     */
    public function findAllByModel(string $modelUuid): array {
        $sql = $this->getJoinStatement(static::MODEL_TABLE_NAME, 'model');

        $sql->andWhere(
            $sql->expr()->eq('b.user_id', 'a.user_id'),
            $sql->expr()->eq('b.uuid', $sql->createNamedParameter($modelUuid))
        );

        return $this->findEntities($sql);
    }

    /**
     * @param AbstractRevision $entity
     *
     * @return AbstractRevision
     * @throws DecryptedDataException
     * @throws Exception
     */
    public function insert(Entity $entity): AbstractRevision {
        if($entity->_isDecrypted()) {
            throw new DecryptedDataException($entity);
        }

        return parent::insert($entity);
    }

    /**
     * @param AbstractRevision $entity
     *
     * @return AbstractRevision
     * @throws DecryptedDataException
     * @throws Exception
     */
    public function update(Entity $entity): AbstractRevision {
        if($entity->_isDecrypted()) {
            throw new DecryptedDataException($entity);
        }

        return parent::update($entity);
    }
}