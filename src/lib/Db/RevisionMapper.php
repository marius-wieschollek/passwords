<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.08.17
 * Time: 22:42
 */

namespace OCA\Passwords\Db;

/**
 * Class RevisionMapper
 *
 * @package OCA\Passwords\Db
 */
class RevisionMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_revision';

    /**
     * @param string $user
     * @param int    $password
     *
     * @return Revision[]
     */
    public function findByPassword(string $user, int $password): array {
        $sql = 'SELECT * FROM `*PREFIX*'.self::TABLE_NAME.'`'.
               'WHERE `password` = ? AND `user` = ?';

        return $this->findEntities($sql, [$password, $user]);
    }
}