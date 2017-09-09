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
     * @param string $user
     * @param int    $folder
     *
     * @return Password[]
     */
    public function findByFolder(string $user, int $folder): array {
        $sql = 'SELECT * FROM `*PREFIX*'.self::TABLE_NAME.'`'.
               'WHERE `folder` = ? AND `user` = ?';

        return $this->findEntities($sql, [$folder, $user]);
    }
}