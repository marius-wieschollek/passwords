<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:36
 */

namespace OCA\Passwords\Db;

/**
 * Class TagMapper
 *
 * @package OCA\Passwords\Db
 */
class TagMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_tag';


    /**
     * @param string $passwordUuid
     * @param bool   $includeHidden
     *
     * @return Tag[]
     */
    public function getByPassword(string $passwordUuid, bool $includeHidden = false): array {
        $tagTable = '`*PREFIX*'.static::TABLE_NAME.'`';
        $relationTable  = '`*PREFIX*'.PasswordTagRelationMapper::TABLE_NAME.'`';

        $sql = 'SELECT '.$tagTable.'.* FROM '.$tagTable.
               ' INNER JOIN '.$relationTable.' ON '.$tagTable.'.`uuid` = '.$relationTable.'.`tag`'.
               ' WHERE '.$tagTable.'.`deleted` = 0 AND '.$tagTable.'.`user_id` = ?'.
               ' AND '.$relationTable.'.`password` = ? AND '.$relationTable.'.`deleted` = 0 AND '.$relationTable.
               '.`user_id` = ?';

        if(!$includeHidden) $sql .= ' AND '.$relationTable.'.`hidden` = 0';

        //var_dump($sql);

        return $this->findEntities($sql, [$this->userId, $passwordUuid, $this->userId]);
    }
}