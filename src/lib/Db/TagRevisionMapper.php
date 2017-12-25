<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 17:14
 */

namespace OCA\Passwords\Db;

class TagRevisionMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_tag_revision';

    protected $allowedFields = ['id', 'uuid', 'model'];
}