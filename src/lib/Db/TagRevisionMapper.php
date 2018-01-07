<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 17:14
 */

namespace OCA\Passwords\Db;

class TagRevisionMapper extends AbstractRevisionMapper {

    const TABLE_NAME = 'passwords_entity_tag_revision';

    const MODEL_TABLE_NAME = 'passwords_entity_tag';
}