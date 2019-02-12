<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Class KeychainMapper
 *
 * @package OCA\Passwords\Db
 */
class KeychainMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_entity_keychain';

    /**
     * @param string $type
     *
     * @return Keychain|\OCP\AppFramework\Db\Entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findOneByType(string $type): Keychain {
        return $this->findOneByField('type', $type);
    }

    /**
     * @param string $scope
     *
     * @return array
     * @throws \Exception
     */
    public function findAllByScope(string $scope): array {
        return $this->findAllByField('scope', $scope);
    }
}