<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class ShareMapper
 *
 * @package OCA\Passwords\Db
 */
class ShareMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_share';

    /**
     * @var array
     */
    protected $allowedFields
        = [
            'id',
            'uuid',
            'source_password',
            'target_password',
            'source_updated',
            'target_updated',
            'expires',
            'receiver'
        ];

    /**
     * @return array
     */
    protected function getStatement(): array {
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE `deleted` = ?';

        $params = [false];
        if($this->userId !== null) {
            $sql      .= ' AND (`*PREFIX*'.static::TABLE_NAME.'`.`user_id` = ?'.
                         ' OR (`*PREFIX*'.static::TABLE_NAME.'`.`receiver` = ? AND `*PREFIX*'.static::TABLE_NAME.'`.`target_password` IS NOT NULL)) ';
            $params[] = $this->userId;
            $params[] = $this->userId;
        }

        return [$sql, $params];
    }
}