<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.10.17
 * Time: 16:49
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordTagRelationMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordTagRelationMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_relation_password_tag';

    protected $allowedFields = ['id', 'user', 'password', 'tag', 'created', 'updated'];
}