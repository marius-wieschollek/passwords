<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Hooks\Manager\HookManager;

/**
 * Class PasswordService
 *
 * @package OCA\Passwords\Services
 */
class PasswordService extends AbstractModelService {

    /**
     * @var PasswordMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Password::class;

    /**
     * PasswordService constructor.
     *
     * @param null|string    $userId
     * @param HookManager    $hookManager
     * @param PasswordMapper $mapper
     */
    public function __construct(?string $userId, HookManager $hookManager, PasswordMapper $mapper) {
        parent::__construct($userId, $hookManager, $mapper);
    }

    /**
     * @param string $uuid
     *
     * @return Password[]
     */
    public function findByFolder(string $uuid): array {
        return $this->mapper->getByFolder($uuid);
    }

    /**
     * @param string $tagUuid
     * @param bool   $includeHidden
     *
     * @return Password[]
     */
    public function findByTag(string $tagUuid, bool $includeHidden = false): array {
        return $this->mapper->getByTag($tagUuid, $includeHidden);
    }

    /**
     * @return Password[]
     * @throws \Exception
     */
    public function findShared(): array {
        return $this->mapper->findAllMatching(['has_shares', true]);
    }

    /**
     * @return Password[]
     */
    public function findOrphanedTargetPasswords(): array {
        return $this->mapper->findOrphanedTargetPasswords();
    }

    /**
     * @return Password
     */
    protected function createModel(): ModelInterface {
        /** @var Password $model */
        $model = parent::createModel();
        $model->setEditable(true);

        return $model;
    }
}