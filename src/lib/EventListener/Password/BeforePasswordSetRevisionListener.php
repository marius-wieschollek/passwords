<?php

namespace OCA\Passwords\EventListener\Password;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Events\Password\BeforePasswordSetRevisionEvent;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class BeforePasswordSetRevisionListener
 *
 * @package OCA\Passwords\EventListener\Password
 */
class BeforePasswordSetRevisionListener extends AbstractPasswordListener implements IEventListener {

    /**
     * @param Event $event
     *
     * @throws DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws Exception
     */
    public function handle(Event $event): void {
        if(!($event instanceof BeforePasswordSetRevisionEvent)) return;
        $password    = $event->getPassword();
        $newRevision = $event->getRevision();

        if($password->getRevision() === null) {
            $this->checkSecurityStatus($newRevision);
            $this->revisionService->save($newRevision);

            return;
        }

        if($password->isEditable() && ($password->getShareId() || $password->hasShares())) {
            $this->updateShares($password);
        }

        /** @var PasswordRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($password->getRevision());
        if($oldRevision->getHidden() !== $newRevision->getHidden()) {
            $this->updateRelations($password, $newRevision);
        }

        if($newRevision->getStatus() === 0) {
            if($newRevision->getHash() === $oldRevision->getHash()) {
                $newRevision->setStatus($oldRevision->getStatus());
                $newRevision->setStatusCode($oldRevision->getStatusCode());
            } else {
                $this->checkSecurityStatus($newRevision);
            }
            $this->revisionService->save($newRevision);
        }
    }
}