<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TagMapper
 *
 * @package OCA\Passwords\Db
 */
class TagMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_tag';

    /**
     * @param string $passwordUuid
     * @param bool   $includeHidden
     *
     * @return Tag[]
     */
    public function findAllByPassword(string $passwordUuid, bool $includeHidden = false): array {
        $sql = $this->getJoinStatement(PasswordTagRelationMapper::TABLE_NAME, 'uuid', 'tag');

        $sql->andWhere(
            $sql->expr()->eq('b.password', $sql->createNamedParameter($passwordUuid))
        )->andWhere(
            $sql->expr()->eq('b.deleted', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
        );

        if(!$includeHidden) {
            $sql->andWhere(
                $sql->expr()->eq('b.hidden', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
            );
        }

        return $this->findEntities($sql);
    }
}