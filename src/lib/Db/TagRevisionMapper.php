<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class TagRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
class TagRevisionMapper extends AbstractRevisionMapper {

    const TABLE_NAME = 'passwords_entity_tag_revision';

    const MODEL_TABLE_NAME = 'passwords_entity_tag';
}