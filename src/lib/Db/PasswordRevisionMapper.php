<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class PasswordRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordRevisionMapper extends AbstractRevisionMapper {

    const TABLE_NAME = 'passwords_entity_password_revision';

    const MODEL_TABLE_NAME = 'passwords_entity_password';

    /**
     * @param string      $hash
     * @param string      $model
     * @param string|null $user
     *
     * @return bool
     */
    public function hasDuplicates(string $hash, string $model, string $user = null): bool {
        $sql = $this->getStatement();

        $sql->andWhere(
            $sql->expr()->eq('hash', $sql->createNamedParameter($hash, IQueryBuilder::PARAM_STR)),
            $sql->expr()->eq('model', $sql->createNamedParameter($model, IQueryBuilder::PARAM_STR))
        );

        if($user !== null) {
            $sql->andWhere(
                $sql->expr()->eq('user_id', $sql->createNamedParameter($user, IQueryBuilder::PARAM_STR))
            );
        }

        return count($this->findEntities($sql)) !== 0;
    }

    /**
     * @return PasswordRevision[]
     */
    public function findAllWithGoodStatus(): array {
        $sql = $this->getStatement();

        $sql->andWhere(
            $sql->expr()->neq('status', $sql->createNamedParameter(2, IQueryBuilder::PARAM_INT))
        );

        return $this->findEntities($sql);
    }
}