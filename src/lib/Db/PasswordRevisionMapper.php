<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordRevisionMapper extends AbstractRevisionMapper {

    const TABLE_NAME = 'passwords_entity_password_revision';

    const MODEL_TABLE_NAME = 'passwords_entity_password';

    protected $allowedFields = ['id', 'uuid', 'model', 'status'];

    /**
     * @param string      $hash
     * @param string      $model
     * @param string|null $user
     *
     * @return bool
     */
    public function hasDuplicates(string $hash, string $model, string $user = null): bool {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `hash` = ? AND model != ?';
        $params[] = $hash;
        $params[] = $model;

        if($user !== null) {
            $sql      .= ' AND `user_id` = ?';
            $params[] = $user;
        }

        return count($this->findEntities($sql, $params)) !== 0;
    }
}