<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class KeychainMapper
 *
 * @package OCA\Passwords\Db
 */
class KeychainMapper extends AbstractMapper {

    const TABLE_NAME = 'passwords_keychain';

    /**
     * @param string $type
     *
     * @return Keychain|Entity
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function findOneByType(string $type): Keychain {
        return $this->findOneByField('type', $type);
    }

    /**
     * @param string $scope
     *
     * @return array
     * @throws Exception
     */
    public function findAllByScope(string $scope): array {
        return $this->findAllByField('scope', $scope);
    }
}