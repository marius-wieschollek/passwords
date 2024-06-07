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

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Events\Password\PasswordClonedEvent;
use OCA\Passwords\Services\PasswordSecurityCheckService;
use OCP\EventDispatcher\Event;

/**
 * Class PasswordClonedListener
 *
 * @package OCA\Passwords\EventListener\Password
 */
class PasswordClonedListener extends AbstractPasswordListener {

    public function handle(Event $event): void {
        if(!($event instanceof PasswordClonedEvent)) return;
        $originalPassword = $event->getOriginal();
        $clonedPassword   = $event->getClone();

        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($originalPassword->getUuid());

        $duplicateHashes       = [];
        $currentClonedRevision = null;
        foreach($revisions as $revision) {
            if($revision->getStatusCode() !== PasswordSecurityCheckService::STATUS_DUPLICATE) $duplicateHashes[] = $revision->getHash();
            /** @var PasswordRevision $revisionClone */
            $revisionClone = $this->revisionService->clone($revision, ['model' => $clonedPassword->getUuid()]);
            $this->revisionService->save($revisionClone);
            if($revision->getUuid() === $originalPassword->getRevision()) {
                $clonedPassword->setRevision($revisionClone->getUuid());
                $currentClonedRevision = $revisionClone;
            }
        }
        $this->updateDuplicateStatus($duplicateHashes);

        $relations = $this->relationService->findByPassword($originalPassword->getUuid());
        foreach($relations as $relation) {
            $relationClone = $this->relationService->clone($relation, [
                'password'         => $currentClonedRevision->getModel(),
                'passwordRevision' => $currentClonedRevision->getUuid(),
                'hidden'           => $currentClonedRevision->isHidden() || $relation->isHidden()
            ]);
            $this->relationService->save($relationClone);
        }
    }
}