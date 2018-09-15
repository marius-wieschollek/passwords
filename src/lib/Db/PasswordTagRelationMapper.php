<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class PasswordTagRelationMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordTagRelationMapper extends AbstractMapper {
    const TABLE_NAME = 'passwords_relation_password_tag';

    /**
     * @param string $tagUuid
     * @param string $passwordUuid
     *
     * @return PasswordTagRelation|Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findOneByTagAndPassword(string $tagUuid, string $passwordUuid): PasswordTagRelation {
        $sql = $this->getStatement();

        $sql->andWhere(
            $sql->expr()->eq('password', $sql->createNamedParameter($passwordUuid, IQueryBuilder::PARAM_STR)),
            $sql->expr()->eq('tag', $sql->createNamedParameter($tagUuid, IQueryBuilder::PARAM_STR))
        );

        return $this->findEntity($sql);
    }
}