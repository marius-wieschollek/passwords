<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class ShareMapper
 *
 * @package OCA\Passwords\Db
 */
class ShareMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_share';

    /**
     * @param int $time
     *
     * @return Share[]
     * @throws \Exception
     */
    public function findAllExpired(int $time): array {
        return $this->findAllByField('expires', $time, IQueryBuilder::PARAM_INT, 'lte');
    }

    /**
     * @param string $passwordUuid
     * @param string $userId
     *
     * @return Share|Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findOneBySourcePasswordAndReceiver(string $passwordUuid, string $userId): Share {
        return $this->findOneByFields(
            ['source_password', $passwordUuid, IQueryBuilder::PARAM_STR],
            ['receiver', $userId, IQueryBuilder::PARAM_STR]
        );
    }

    /**
     * @return IQueryBuilder
     */
    protected function getStatement(): IQueryBuilder {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from(static::TABLE_NAME)
           ->where(
               $qb->expr()->eq('deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
           );

        if($this->userId !== null) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId, IQueryBuilder::PARAM_STR)),
                    $qb->expr()->andX(
                        $qb->expr()->eq('receiver', $qb->createNamedParameter($this->userId, IQueryBuilder::PARAM_STR)),
                        $qb->expr()->isNotNull('target_password')
                    )
                )
            );
        }

        return $qb;
    }
}