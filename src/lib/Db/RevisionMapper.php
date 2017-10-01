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

    protected $allowedFields = ['id', 'uuid', 'password_id', 'status'];
}