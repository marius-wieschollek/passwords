<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:33
 */

namespace OCA\Passwords\Db;

/**
 * Class FolderMapper
 *
 * @package OCA\Passwords\Db
 */
class FolderMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_folder';

    /**
     * @param string $parentUuid
     *
     * @return Folder[]
     */
    public function getByParentFolder(string $parentUuid): array {
        $folderTable   = '`*PREFIX*'.static::TABLE_NAME.'`';
        $revisionTable = '`*PREFIX*'.FolderRevisionMapper::TABLE_NAME.'`';

        $sql = "SELECT {$folderTable}.* FROM {$folderTable}".
               " INNER JOIN {$revisionTable} ON {$folderTable}.`revision` = {$revisionTable}.`uuid`".
               " WHERE {$folderTable}.`deleted` = false".
               ($this->userId !== null ? " AND {$folderTable}.`user_id` = ?":'').
               " AND {$revisionTable}.`parent` = ? AND {$revisionTable}.`deleted` = false".
               ($this->userId !== null ? " AND {$revisionTable}.`user_id` = ?":'');

        $params = [$parentUuid];
        if($this->userId !== null) $params = [$this->userId, $parentUuid, $this->userId];

        return $this->findEntities($sql, $params);
    }

}