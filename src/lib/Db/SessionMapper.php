<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class SessionMapper
 *
 * @package OCA\Passwords\Db
 */
class SessionMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_session';

    /**
     * @param int $updated
     *
     * @return Session[]
     */
    public function findAllOlderThan(int $updated): array {
        $sql = $this->getStatement();

        $sql->where(
            $sql->expr()->lte('updated', $sql->createNamedParameter($updated, IQueryBuilder::PARAM_INT))
        );

        return $this->findEntities($sql);
    }

}