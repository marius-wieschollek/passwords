<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 14:15
 */

namespace OCA\Passwords\Db;

/**
 * Class ShareMapper
 *
 * @package OCA\Passwords\Db
 */
class ShareMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_share';

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
        $sql = 'SELECT * FROM `*PREFIX*'.static::TABLE_NAME.'` WHERE `deleted` = 0';

        $params = [];
        if($this->userId !== null) {
            $sql .= ' AND (`*PREFIX*'.static::TABLE_NAME.'`.`user_id` = ? OR `*PREFIX*'.static::TABLE_NAME.'`.`receiver` = ?) ';
            $params[] = $this->userId;
            $params[] = $this->userId;
        }

        return [$sql, $params];
    }
}