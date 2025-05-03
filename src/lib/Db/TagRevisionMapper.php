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

    const string TABLE_NAME = 'passwords_tag_rv';

    const string MODEL_TABLE_NAME = 'passwords_tag';
}