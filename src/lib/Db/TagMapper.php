<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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

        $sql = "SELECT {$tagTable}.* FROM {$tagTable} ".
               "INNER JOIN {$relationTable} ON {$tagTable}.`uuid` = {$relationTable}.`tag` ".
               "WHERE {$tagTable}.`deleted` = ? ".
               "AND {$relationTable}.`deleted` = ? ".
               "AND {$relationTable}.`password` = ?";

        $params = [false, false, $passwordUuid];
        if($this->userId !== null) {
            $sql      .= " AND {$tagTable}.`user_id` = ?";
            $sql      .= " AND {$relationTable}.`user_id` = ?";
            $params[] = $this->userId;
            $params[] = $this->userId;
        }
        if(!$includeHidden) {
            $sql      .= " AND {$relationTable}.`hidden` = ?";
            $params[] = false;
        }

        return $this->findEntities($sql, $params);
    }
}