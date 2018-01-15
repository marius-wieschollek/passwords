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
        $tagTable      = '`*PREFIX*'.static::TABLE_NAME.'`';
        $relationTable = '`*PREFIX*'.PasswordTagRelationMapper::TABLE_NAME.'`';

        $sql = "SELECT {$tagTable}.* FROM {$tagTable}".
               " INNER JOIN {$relationTable} ON {$tagTable}.`uuid` = {$relationTable}.`tag`".
               " WHERE {$tagTable}.`deleted` = false".
               ($this->userId !== null ? " AND {$tagTable}.`user_id` = ?":'').
               " AND {$relationTable}.`password` = ? AND {$relationTable}.`deleted` = false".
               ($this->userId !== null ? " AND {$relationTable}.`user_id` = ?":'');

        if(!$includeHidden) $sql .= " AND {$relationTable}.`hidden` = false";

        $params = [$passwordUuid];
        if($this->userId !== null) $params = [$this->userId, $passwordUuid, $this->userId];

        return $this->findEntities($sql, $params);
    }
}