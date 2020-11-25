<?php
/*
 * @copyright 2020 Passwords App
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
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;

abstract class AbstractPasswordListener {

    /**
     * @var ShareService
     */
    protected ShareService $shareService;

    /**
     * @var PasswordService
     */
    protected PasswordService $passwordService;

    /**
     * @var PasswordRevisionService
     */
    protected PasswordRevisionService $revisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected PasswordTagRelationService $relationService;

    /**
     * @var TagRevisionService
     */
    protected TagRevisionService $tagRevisionService;

    /**
     * @var HelperService
     */
    protected HelperService $helperService;

    /**
     * PasswordHook constructor.
     *
     * @param ShareService               $shareService
     * @param HelperService              $helperService
     * @param TagRevisionService         $tagRevisionService
     * @param PasswordRevisionService    $revisionService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        ShareService $shareService,
        HelperService $helperService,
        TagRevisionService $tagRevisionService,
        PasswordRevisionService $revisionService,
        PasswordTagRelationService $relationService
    ) {
        $this->shareService       = $shareService;
        $this->helperService      = $helperService;
        $this->revisionService    = $revisionService;
        $this->relationService    = $relationService;
        $this->tagRevisionService = $tagRevisionService;
    }

    /**
     * @param PasswordRevision $revision
     * @param bool             $searchDuplicates
     *
     * @throws Exception
     */
    protected function checkSecurityStatus(PasswordRevision $revision, bool $searchDuplicates = true): void {
        $securityCheck = $this->helperService->getSecurityHelper();
        [$status, $statusCode] = $securityCheck->getRevisionSecurityLevel($revision);
        $revision->setStatus($status);
        $revision->setStatusCode($statusCode);

        if($searchDuplicates && $statusCode === AbstractSecurityCheckHelper::STATUS_DUPLICATE) {
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