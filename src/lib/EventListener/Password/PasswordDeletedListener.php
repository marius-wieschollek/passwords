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
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Events\Password\PasswordDeletedEvent;
use OCA\Passwords\Services\PasswordSecurityCheckService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\Event;

/**
 * Class PasswordDeletedListener
 *
 * @package OCA\Passwords\EventListener\Password
 */
class PasswordDeletedListener extends AbstractPasswordListener {

    /**
     * @param Event $event
     *
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof PasswordDeletedEvent)) return;
        $password = $event->getPassword();

        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($password->getUuid());

        $duplicateHashes = [];
        foreach($revisions as $revision) {
            if($revision->getStatusCode() === PasswordSecurityCheckService::STATUS_DUPLICATE) $duplicateHashes[] = $revision->getHash();
            $this->revisionService->delete($revision);
        }

        $this->updateDuplicateStatus($duplicateHashes);

        if($password->getShareId()) {
            try {
                $share = $this->shareService->findByTargetPassword($password->getUuid());
                if($share !== null) $this->shareService->delete($share);
            } catch(DoesNotExistException $e) {
            }
        }
    }
}