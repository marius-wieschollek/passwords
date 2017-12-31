<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.10.17
 * Time: 23:23
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\ShareRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Class PasswordHook
 *
 * @package OCA\Passwords\Hooks\Password
 */
class PasswordHook {

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $relationService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var ShareRevisionService
     */
    protected $shareRevisionService;

    /**
     * PasswordHook constructor.
     *
     * @param ShareService               $shareService
     * @param TagRevisionService         $tagRevisionService
     * @param PasswordRevisionService    $revisionService
     * @param ShareRevisionService       $shareRevisionService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        ShareService $shareService,
        TagRevisionService $tagRevisionService,
        PasswordRevisionService $revisionService,
        ShareRevisionService $shareRevisionService,
        PasswordTagRelationService $relationService
    ) {
        $this->shareService         = $shareService;
        $this->revisionService      = $revisionService;
        $this->relationService      = $relationService;
        $this->tagRevisionService   = $tagRevisionService;
        $this->shareRevisionService = $shareRevisionService;
    }

    /**
     * @param Password         $password
     * @param PasswordRevision $newRevision
     *
     * @throws DoesNotExistException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function preSetRevision(Password $password, PasswordRevision $newRevision): void {
        if($password->getShareId() || $newRevision->isShared()) {
            if($password->getRevision() === null) return;
        }
        /** @var PasswordRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($password->getRevision());

        if($oldRevision->getHidden() != $newRevision->getHidden()) {
            $relations = $this->relationService->findByPassword($password->getUuid());

            foreach ($relations as $relation) {
                /** @var TagRevision $tagRevision */
                $tagRevision = $this->tagRevisionService->findByModel($relation->getTag());
                $relation->setHidden($newRevision->isHidden() || $tagRevision->isHidden());
                $this->relationService->save($relation);
            }
        }
    }

    /**
     * @param Password         $password
     * @param PasswordRevision $revision
     *
     * @throws DoesNotExistException
     * @throws \Exception
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updateShares(Password $password, PasswordRevision $revision) {
        if(!$password->isEditable() || !$revision->_isDecrypted()) return;
        if($password->getShareId()) {
            $share = $this->shareService->findByUuid($password->getShareId());
            /** @var ShareRevision $shareRevision */
            $shareRevision = $this->shareRevisionService->findByUuid($share->getUuid());

            if($shareRevision->isEditable()) {
                $revision = $this->shareRevisionService->create(
                    $share->getUuid(),
                    $revision->getPassword(),
                    $revision->getUsername(),
                    $revision->getUrl(),
                    $revision->getLabel(),
                    $revision->getNotes(),
                    $revision->getHash(),
                    $revision->getCseType(),
                    true
                );
                $this->shareRevisionService->save($revision);
                $this->shareService->setRevision($share, $revision);
            }
        }

        if($revision->isShared()) {
            $shares = $this->shareService->findByPassword($password->getUuid());
            foreach ($shares as $share) {
                /** @var ShareRevision $shareRevision */
                $shareRevision = $this->shareRevisionService->findByUuid($share->getUuid());

                $revision = $this->shareRevisionService->create(
                    $share->getUuid(),
                    $revision->getPassword(),
                    $revision->getUsername(),
                    $revision->getUrl(),
                    $revision->getLabel(),
                    $revision->getNotes(),
                    $revision->getHash(),
                    $revision->getCseType(),
                    $shareRevision->isEditable()
                );
                $this->shareRevisionService->save($revision);
                $this->shareService->setRevision($share, $revision);
            }
        }
    }

    /**
     * @param Password $password
     *
     * @throws \Exception
    // @TODO delete shares
     */
    public function preDelete(Password $password): void {
        $relations = $this->relationService->findByPassword($password->getUuid());

        foreach ($relations as $relation) {
            $this->relationService->delete($relation);
        }
    }

    /**
     * @param Password $password
     *
     * @throws \Exception
     */
    public function postDelete(Password $password): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($password->getUuid());

        foreach ($revisions as $revision) {
            $this->revisionService->delete($revision);
        }
    }

    /**
     * @param Password $originalPassword
     * @param Password $clonedPassword
     *
     * @throws \Exception
     */
    public function postClone(Password $originalPassword, Password $clonedPassword): void {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($originalPassword->getUuid());

        $currentClonedRevision = null;
        foreach ($revisions as $revision) {
            /** @var PasswordRevision $revisionClone */
            $revisionClone = $this->revisionService->clone($revision, ['model' => $clonedPassword->getUuid()]);
            $this->revisionService->save($revisionClone);
            if($revision->getUuid() == $originalPassword->getRevision()) {
                $clonedPassword->setRevision($revisionClone->getUuid());
                $currentClonedRevision = $revisionClone;
            }
        }

        $relations = $this->relationService->findByPassword($originalPassword->getUuid());
        foreach ($relations as $relation) {
            $relationClone = $this->relationService->clone($relation, [
                'password'         => $currentClonedRevision->getModel(),
                'passwordRevision' => $currentClonedRevision->getUuid(),
                'hidden'           => $currentClonedRevision->isHidden() || $relation->isHidden()
            ]);
            $this->relationService->save($relationClone);
        }
    }
}