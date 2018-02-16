<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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

        $sql = "SELECT {$folderTable}.* FROM {$folderTable} ".
               "INNER JOIN {$revisionTable} ON {$folderTable}.`revision` = {$revisionTable}.`uuid` ".
               "WHERE {$folderTable}.`deleted` = ? ".
               "AND {$revisionTable}.`deleted` = ? ".
               "AND {$revisionTable}.`parent` = ?";

        $params = [false, false, $parentUuid];
        if($this->userId !== null) {
            $sql      .= " AND {$folderTable}.`user_id` = ?";
            $sql      .= " AND {$revisionTable}.`user_id` = ?";
            $params[] = $this->userId;
            $params[] = $this->userId;
        }

        return $this->findEntities($sql, $params);
    }

}