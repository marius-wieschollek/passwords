<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.01.18
 * Time: 20:02
 */

namespace OCA\Passwords\Db;

/**
 * Class AbstractRevisionMapper
 *
 * @package OCA\Passwords\Db
 */
abstract class AbstractRevisionMapper extends AbstractMapper {

    const MODEL_TABLE_NAME = 'passwords_model';

    /**
     * @var array
     */
    protected $allowedFields = ['id', 'uuid', 'model'];

    /**
     * @param string $passwordUuid
     *
     * @return null|PasswordRevision|\OCP\AppFramework\Db\Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findCurrentRevisionByModel(string $passwordUuid): ?RevisionInterface {
        $revisionTable = '`*PREFIX*'.static::TABLE_NAME.'`';
        $passwordTable = '`*PREFIX*'.static::MODEL_TABLE_NAME.'`';

        $sql = "SELECT {$revisionTable}.* FROM {$revisionTable} ".
               "INNER JOIN {$passwordTable} ".
               "ON {$revisionTable}.`model` = {$passwordTable}.`uuid` ".
               "WHERE {$revisionTable}.`deleted` = ? ".
               "AND {$passwordTable}.`deleted` = ? ".
               "AND {$passwordTable}.`user_id` = {$revisionTable}.`user_id` ".
               "AND {$passwordTable}.`revision` = {$revisionTable}.`uuid` ".
               "AND {$passwordTable}.`uuid` = ?";

        $params = [false, false, $passwordUuid];
        if($this->userId !== null) {
            $sql      .= " AND {$passwordTable}.`user_id` = ?";
            $params[] = $this->userId;
        }

        return $this->findEntity($sql, $params);
    }
}