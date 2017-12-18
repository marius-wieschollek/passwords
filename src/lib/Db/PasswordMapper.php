<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 20:34
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_password';

    /**
     * @param string $parentUuid
     *
     * @return array
     */
    public function getByFolder(string $parentUuid) {
        $passwordsTable = '`*PREFIX*'.static::TABLE_NAME.'`';
        $revisionTable = '`*PREFIX*'.PasswordRevisionMapper::TABLE_NAME.'`';

        $sql = 'SELECT '.$passwordsTable.'.* FROM '.$passwordsTable.
               'INNER JOIN '.$revisionTable.' ON '.$passwordsTable.'.`revision` = '.$revisionTable.'.`uuid`'.
               ' WHERE '.$passwordsTable.'.`deleted` = 0  AND '.$passwordsTable.'.`user_id` = ?'.
               ' AND '.$revisionTable.'.`folder` = ? AND '.$revisionTable.'.`deleted` = 0  AND '.$revisionTable.'.`user_id` = ?';

        return $this->findEntities($sql, [$this->userId, $parentUuid, $this->userId]);
    }
}