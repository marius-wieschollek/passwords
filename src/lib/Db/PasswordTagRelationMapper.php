<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class PasswordTagRelationMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordTagRelationMapper extends AbstractMapper {
    const TABLE_NAME = 'passwords_relation_password_tag';
}