<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordRevisionMapper extends AbstractRevisionMapper {

    const TABLE_NAME = 'passwords_entity_password_revision';

    const MODEL_TABLE_NAME = 'passwords_entity_password';

    protected $allowedFields = ['id', 'uuid', 'model', 'status'];
}