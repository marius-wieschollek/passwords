<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.10.17
 * Time: 16:36
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;

class PasswordFolderRelationMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_relation_password_folder';

    protected $allowedFields       = ['id', 'user', 'password', 'folder', 'created', 'updated'];

    /**
     * @param string $password
     *
     * @return PasswordFolderRelation[]|Entity[]
     *
     */
    public function findByPassword(string $password): array {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `password` = ?';
        $params[] = $password;

        return $this->findEntities($sql, $params);
    }

    /**
     * @param string $folder
     *
     * @return PasswordFolderRelation[]|Entity[]
     *
     */
    public function findByFolder(string $folder): array {
        list($sql, $params) = $this->getStatement();

        $sql      .= ' AND `folder` = ?';
        $params[] = $folder;

        return $this->findEntities($sql, $params);
    }
}