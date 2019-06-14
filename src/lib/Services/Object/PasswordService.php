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
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;

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
     * @param UuidHelper         $uuidHelper
     * @param HookManager        $hookManager
     * @param PasswordMapper     $mapper
     * @param EnvironmentService $environment
     */
    public function __construct(UuidHelper $uuidHelper, HookManager $hookManager, PasswordMapper $mapper, EnvironmentService $environment) {
        parent::__construct($mapper, $uuidHelper, $hookManager, $environment);
    }

    /**
     * @param string $uuid
     *
     * @return Password[]
     */
    public function findByFolder(string $uuid): array {
        return $this->mapper->findAllByFolder($uuid);
    }

    /**
     * @param string $tagUuid
     * @param bool   $includeHidden
     *
     * @return Password[]
     */
    public function findByTag(string $tagUuid, bool $includeHidden): array {
        return $this->mapper->findAllByTag($tagUuid, $includeHidden);
    }

    /**
     * @return Password[]
     * @throws \Exception
     */
    public function findShared(): array {
        return $this->mapper->findAllShared();
    }

    /**
     * @return Password[]
     */
    public function findOrphanedTargetPasswords(): array {
        return $this->mapper->findAllOrphanedTargetPasswords();
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