<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\EventListener\Password;

use Exception;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\PasswordSecurityCheckService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventListener;

/**
 * Class AbstractPasswordListener
 *
 * @package OCA\Passwords\EventListener\Password
 */
abstract class AbstractPasswordListener implements IEventListener {

    /**
     * PasswordHook constructor.
     *
     * @param ShareService                 $shareService
     * @param TagRevisionService           $tagRevisionService
     * @param PasswordRevisionService      $revisionService
     * @param PasswordTagRelationService   $relationService
     * @param PasswordSecurityCheckService $passwordSecurityCheckService
     */
    public function __construct(
        protected ShareService $shareService,
        protected TagRevisionService $tagRevisionService,
        protected PasswordRevisionService $revisionService,
        protected PasswordTagRelationService $relationService,
        protected PasswordSecurityCheckService $passwordSecurityCheckService
    ) {
    }

    /**
     * @param PasswordRevision $revision
     * @param bool             $searchDuplicates
     *
     * @throws Exception
     */
    protected function checkSecurityStatus(PasswordRevision $revision, bool $searchDuplicates = true): void {
        [$status, $statusCode] = $this->passwordSecurityCheckService->getRevisionSecurityLevel($revision);
        $revision->setStatus($status);
        $revision->setStatusCode($statusCode);

        if($searchDuplicates && $statusCode === PasswordSecurityCheckService::STATUS_DUPLICATE) {
            $this->updateDuplicateStatus([$revision->getHash()]);
        }
    }

    /**
     * @param array $hashes
     */
    protected function updateDuplicateStatus(array $hashes): void {
        $hashes = array_unique($hashes);

        foreach($hashes as $hash) {
            try {
                $revisions = $this->revisionService->findByHash($hash);
                foreach($revisions as $revision) {
                    $oldStatus = $revision->getStatusCode();
                    $this->checkSecurityStatus($revision, false);
                    if($oldStatus !== $revision->getStatusCode()) $this->revisionService->save($revision);
                }
            } catch(Exception $e) {
            }
        }
    }

    /**
     * @param Password $password
     *
     * @throws Exception
     */
    protected function updateShares(Password $password): void {
        if($password->getShareId()) {
            try {
                $share = $this->shareService->findByTargetPassword($password->getUuid());
                $share->setTargetUpdated(true);
                $this->shareService->save($share);
            } catch(DoesNotExistException $e) {
            }
        }

        if($password->hasShares()) {
            $shares = $this->shareService->findBySourcePassword($password->getUuid());
            foreach($shares as $share) {
                $share->setSourceUpdated(true);
                $this->shareService->save($share);
            }
        }
    }

    /**
     * @param Password         $password
     * @param PasswordRevision $newRevision
     *
     * @throws Exception
     */
    protected function updateRelations(Password $password, PasswordRevision $newRevision): void {
        $relations = $this->relationService->findByPassword($password->getUuid());

        foreach($relations as $relation) {
            /** @var TagRevision $tagRevision */
            $tagRevision = $this->tagRevisionService->findByModel($relation->getTag());
            $relation->setHidden($newRevision->isHidden() || $tagRevision->isHidden());
            $this->relationService->save($relation);
        }
    }
}