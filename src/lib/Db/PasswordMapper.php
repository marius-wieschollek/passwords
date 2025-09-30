<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use Exception;
use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class PasswordMapper
 *
 * @package OCA\Passwords\Db
 */
class PasswordMapper extends AbstractMapper {

    const string TABLE_NAME = 'passwords_password';

    /**
     * @return Password[]
     * @throws Exception
     */
    public function findAllShared(): array {
        return $this->findAllByField('has_shares', true, IQueryBuilder::PARAM_BOOL);
    }

    /**
     * @param string $shareUuid
     * @param bool   $source
     *
     * @return null|Password|Entity
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findPasswordByShare(string $shareUuid, bool $source = true): ?Password {
        $sql      = $this->db->getQueryBuilder();
        $mapField = $source ? 'source_password':'target_password';

        $sql->select('a.*')
            ->from(static::TABLE_NAME, 'a')
            ->innerJoin('a', ShareMapper::TABLE_NAME, 'b', "a.`uuid` = b.`{$mapField}`")
            ->where(
                $sql->expr()->eq('a.deleted', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)),
                $sql->expr()->eq('b.deleted', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)),
                $sql->expr()->eq('b.uuid', $sql->createNamedParameter($shareUuid))
            )->andWhere(
                $sql->expr()->orX(
                    $sql->expr()->eq('a.user_id', 'b.user_id'),
                    $sql->expr()->eq('a.user_id', 'b.receiver')
                )
            );

        if ($this->userId !== null) {
            $sql->andWhere(
                $sql->expr()->eq('b.user_id', $sql->createNamedParameter($this->userId)),
                $sql->expr()->eq('b.receiver', $sql->createNamedParameter($this->userId))
            );
        }

        return $this->findEntity($sql);
    }

    /**
     * @return Password[]
     */
    public function findAllOrphanedTargetPasswords(): array {
        $sql = $this->db->getQueryBuilder();

        $sql->select('a.*')
            ->from(static::TABLE_NAME, 'a')
            ->innerJoin('a', ShareMapper::TABLE_NAME, 'b', 'a.`uuid` = b.`target_password`')
            ->where(
                $sql->expr()->eq('a.deleted', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)),
                $sql->expr()->eq('b.deleted', $sql->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
            )
            ->orWhere(
                $sql->expr()->isNull('b.uuid')
            );

        return $this->findEntities($sql);
    }

    /**
     * @return Password[]
     */

     public function findAllSharedPasswordsWithoutShares() {
        $sql = $this->db->getQueryBuilder();
        $sql->select('a.*')
            ->from(static::TABLE_NAME, 'a')
            ->leftJoin('a', ShareMapper::TABLE_NAME, 'b', 'a.`uuid` = b.`source_password`')
            ->where(
                $sql->expr()->eq('a.has_shares', $sql->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)),
                $sql->expr()->isNull('b.id')
            );
        return $this->findEntities($sql);
    }

    /**
     * @param string $folderUuid
     *
     * @return Password[]
     */
    public function findAllByFolder(string $folderUuid): array {
        $sql = $this->getJoinStatement(PasswordRevisionMapper::TABLE_NAME);

        $sql->andWhere(
            $sql->expr()->eq('b.folder', $sql->createNamedParameter($folderUuid))
        );

        return $this->findEntities($sql);
    }

    /**
     * @param string $tagUuid
     * @param bool   $includeHidden
     *
     * @return Password[]
     */
    public function findAllByTag(string $tagUuid, bool $includeHidden = false): array {
        $sql = $this->getJoinStatement(PasswordTagRelationMapper::TABLE_NAME, 'uuid', 'password');

        $sql->andWhere(
            $sql->expr()->eq('b.tag', $sql->createNamedParameter($tagUuid))
        )->andWhere(
            $sql->expr()->eq('b.deleted', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
        );

        if (!$includeHidden) {
            $sql->andWhere(
                $sql->expr()->eq('b.hidden', $sql->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
            );
        }

        return $this->findEntities($sql);
    }
}