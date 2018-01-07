<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 17:09
 */

namespace OCA\Passwords\Db;

class FolderRevisionMapper extends AbstractRevisionMapper {

    const TABLE_NAME = 'passwords_entity_folder_revision';

    const MODEL_TABLE_NAME = 'passwords_entity_folder';

    protected $allowedFields = ['id', 'uuid', 'model'];

}