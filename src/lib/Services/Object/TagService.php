<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EnvironmentService;

/**
 * Class TagService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagService extends AbstractModelService {

    /**
     * @var TagMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Tag::class;

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * TagService constructor.
     *
     * @param HookManager        $hookManager
     * @param TagMapper          $mapper
     * @param EnvironmentService $environment
     */
    public function __construct(HookManager $hookManager, TagMapper $mapper, EnvironmentService $environment) {
        $this->userId      = $environment->getUserId();
        $this->hookManager = $hookManager;
        $this->mapper      = $mapper;
    }

    /**
     * @param string $passwordUuid
     * @param bool   $includeHidden
     *
     * @return Tag[]
     */
    public function findByPassword(string $passwordUuid, bool $includeHidden = false): array {
        return $this->mapper->getByPassword($passwordUuid, $includeHidden);
    }
}