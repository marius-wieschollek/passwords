<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 20:34
 */

namespace OCA\Passwords\Db;

/**
 * Class PasswordMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_password';

    /**
     * @var array
     */
    protected $allowedFields       = ['id', 'uuid', 'has_shares'];

    /**
     * @param string $shareUuid
     * @param bool   $source
     *
     * @return null|Password|\OCP\AppFramework\Db\Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findPasswordByShare(string $shareUuid, bool $source = true): ?Password {
        $passwordTable = '`*PREFIX*'.PasswordMapper::TABLE_NAME.'`';
        $shareTable    = '`*PREFIX*'.ShareMapper::TABLE_NAME.'`';
        $mapField = $source ? 'source_password':'target_password';

        $sql = "SELECT {$passwordTable}.* FROM {$passwordTable} ".
               "INNER JOIN {$shareTable} ".
               "ON {$passwordTable}.`uuid` = {$shareTable}.`{$mapField}` ".
               "WHERE {$passwordTable}.`deleted` = 0 ".
               "AND {$shareTable}.`deleted` = 0 ".
               "AND ( {$passwordTable}.`user_id` = {$shareTable}.`user_id` ".
               "OR {$passwordTable}.`user_id` = {$shareTable}.`receiver` ) ".
               "AND {$shareTable}.`uuid` = ?";

        $params = [$shareUuid];
        if($this->userId !== null) {
            $sql      .= " AND ({$shareTable}.`user_id` = ? OR {$shareTable}.`receiver` = ? )";
            $params[] = $this->userId;
            $params[] = $this->userId;
        }

        return $this->findEntity($sql, $params);
    }

    /**
     * @return Password[]
     */
    public function findOrphanedTargetPasswords(): array {
        $passwordsTable = '`*PREFIX*'.static::TABLE_NAME.'`';
        $shareTable    = '`*PREFIX*'.ShareMapper::TABLE_NAME.'`';

        $sql = "SELECT {$passwordsTable}.* FROM {$passwordsTable} ".
               "INNER JOIN {$shareTable} ".
               "ON {$passwordsTable}.`uuid` = {$shareTable}.`target_password` ".
               "WHERE {$passwordsTable}.`deleted` = 0 ".
               "AND {$shareTable}.`deleted` = 1 ";

        return $this->findEntities($sql);
    }

    /**
     * @param string $folderUuid
     *
     * @return Password[]
     */
    public function getByFolder(string $folderUuid): array {
        $passwordsTable = '`*PREFIX*'.static::TABLE_NAME.'`';
        $revisionTable  = '`*PREFIX*'.PasswordRevisionMapper::TABLE_NAME.'`';

        $sql = "SELECT {$passwordsTable}.* FROM {$passwordsTable}".
               " INNER JOIN {$revisionTable} ON {$passwordsTable}.`revision` = {$revisionTable}.`uuid`".
               " WHERE {$passwordsTable}.`deleted` = 0".
               ($this->userId !== null ? " AND {$passwordsTable}.`user_id` = ?":'').
               " AND {$revisionTable}.`folder` = ? AND {$revisionTable}.`deleted` = 0".
               ($this->userId !== null ? " AND {$revisionTable}.`user_id` = ?":'');

        $params = [$folderUuid];
        if($this->userId !== null) $params = [$this->userId, $folderUuid, $this->userId];

        return $this->findEntities($sql, $params);
    }

    /**
     * @param string $tagUuid
     * @param bool   $includeHidden
     *
     * @return Password[]
     */
    public function getByTag(string $tagUuid, bool $includeHidden = false): array {
        $passwordsTable = '`*PREFIX*'.static::TABLE_NAME.'`';
        $relationTable  = '`*PREFIX*'.PasswordTagRelationMapper::TABLE_NAME.'`';

        $sql = "SELECT {$passwordsTable}.* FROM {$passwordsTable}".
               " INNER JOIN {$relationTable} ON {$passwordsTable}.`uuid` = {$relationTable}.`password`".
               " WHERE {$passwordsTable}.`deleted` = 0".
               ($this->userId !== null ? " AND {$passwordsTable}.`user_id` = ?":'').
               " AND {$relationTable}.`tag` = ? AND {$relationTable}.`deleted` = 0".
               ($this->userId !== null ? " AND {$relationTable}.`user_id` = ?":'');

        if(!$includeHidden) $sql .= " AND {$relationTable}.`hidden` = 0";

        $params = [$tagUuid];
        if($this->userId !== null) $params = [$this->userId, $tagUuid, $this->userId];

        return $this->findEntities($sql, $params);
    }
}