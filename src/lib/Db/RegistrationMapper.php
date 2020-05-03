<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class RegistrationMapper
 *
 * @package OCA\Passwords\Db
 */
class RegistrationMapper extends AbstractMapper {
    const TABLE_NAME = 'passwords_entity_registration';

    /**
     * @param int $created
     *
     * @return Registration[]
     */
    public function findAllOlderThan(int $created): array {
        $sql = $this->getStatement();

        $sql->where(
            $sql->expr()->lte('created', $sql->createNamedParameter($created, IQueryBuilder::PARAM_INT))
        );

        return $this->findEntities($sql);
    }
}