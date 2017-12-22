<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 23:31
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\Object\PasswordRevisionService;

/**
 * Class PasswordRevisionHook
 *
 * @package OCA\Passwords\Hooks
 */
class PasswordRevisionHook {

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * PasswordRevisionHook constructor.
     *
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(PasswordRevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param PasswordRevision $original
     * @param PasswordRevision $clone
     *
     * @TODO Clone tag relations
     */
    public function postClone(PasswordRevision $original, PasswordRevision $clone): void {
    }

    /**
     * @param PasswordRevision $password
     *
     * @TODO Remove from all tags
     */
    public function preDelete(PasswordRevision $password): void {
    }
}