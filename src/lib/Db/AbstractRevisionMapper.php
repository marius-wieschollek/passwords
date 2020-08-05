<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractRevisionMapper extends AbstractMapper {

    const MODEL_TABLE_NAME = 'passwords_model';

    /**
     * @param string $modelUuid
     *
     * @return null|RevisionInterface|\OCP\AppFramework\Db\Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
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
     * @return RevisionInterface[]|\OCP\AppFramework\Db\Entity[]
     */
    public function findAllByModel(string $modelUuid): array {
        $sql = $this->getJoinStatement(static::MODEL_TABLE_NAME, 'model');

        $sql->andWhere(
            $sql->expr()->eq('b.user_id', 'a.user_id'),
            $sql->expr()->eq('b.uuid', $sql->createNamedParameter($modelUuid))
        );

        return $this->findEntities($sql);
    }
}