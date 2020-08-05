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
     * @param string $userId
     *
     * @return Share[]
     * @throws \Exception
     */
    public function findAllByUserIdOrReceiverId(string $userId): array {
        $sql = $this->getStatement();

        $sql->orWhere(
            $sql->expr()->eq('user_id', $sql->createNamedParameter($userId)),
            $sql->expr()->eq('receiver', $sql->createNamedParameter($userId))
        );

        return $this->findEntities($sql);
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
                    $qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)),
                    $qb->expr()->andX(
                        $qb->expr()->eq('receiver', $qb->createNamedParameter($this->userId)),
                        $qb->expr()->isNotNull('target_password')
                    )
                )
            );
        }

        return $qb;
    }
}