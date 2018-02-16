<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordTagRelationMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordTagRelationMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_relation_password_tag';

    protected $allowedFields = ['id', 'user', 'password', 'tag'];
}