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

        $sql = 'SELECT '.$folderTable.'.* FROM '.$folderTable.
               ' INNER JOIN '.$revisionTable.' ON '.$folderTable.'.`revision` = '.$revisionTable.'.`uuid`'.
               ' WHERE '.$folderTable.'.`deleted` = 0 AND '.$folderTable.'.`user_id` = ?'.
               ' AND '.$revisionTable.'.`parent` = ? AND '.$revisionTable.'.`deleted` = 0  AND '.$revisionTable.
               '.`user_id` = ?';

        return $this->findEntities($sql, [$this->userId, $parentUuid, $this->userId]);
    }

}