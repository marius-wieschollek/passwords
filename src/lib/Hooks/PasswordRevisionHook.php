<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 23:31
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\Object\PasswordFolderRelationService;
use OCA\Passwords\Services\Object\PasswordRevisionService;

class PasswordRevisionHook {

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * PasswordHook constructor.
     *
     * @param PasswordRevisionService       $revisionService
     */
    public function __construct(
        PasswordRevisionService $revisionService
    ) {
        $this->revisionService       = $revisionService;
    }

    /**
     * @param PasswordRevision $original
     * @param PasswordRevision $clone
     * @TODO Clone tag relations
     */
    public function postClone(PasswordRevision $original, PasswordRevision $clone) {
    }

    /**
     * @param PasswordRevision $password
     * @TODO Remove from all tags
     */
    public function postDelete(PasswordRevision $password) {
    }
}