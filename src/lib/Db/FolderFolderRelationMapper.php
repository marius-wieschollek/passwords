<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.10.17
 * Time: 16:50
 */

namespace OCA\Passwords\Db;

/**
 * Class FolderFolderRelationMapper
 *
 * @package OCA\Passwords\Db
 */
class FolderFolderRelationMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_relation_folder_folder';

    protected $allowedFields       = ['id', 'user', 'child', 'parent', 'created', 'updated'];

}