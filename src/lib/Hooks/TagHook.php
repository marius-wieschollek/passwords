<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Hooks;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Class TagHook
 *
 * @package OCA\Passwords\Hooks
 */
class TagHook {

    /**
     * @var TagRevisionService
     */
    protected $revisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $relationService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * PasswordHook constructor.
     *
     * @param TagRevisionService         $revisionService
     * @param PasswordRevisionService    $passwordRevisionService
     * @param PasswordTagRelationService $relationService
     */
    public function __construct(
        TagRevisionService $revisionService,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $relationService
    ) {
        $this->revisionService         = $revisionService;
        $this->relationService         = $relationService;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @param Tag         $tag
     * @param TagRevision $newRevision
     *
     * @throws DoesNotExistException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function preSetRevision(Tag $tag, TagRevision $newRevision): void {
        if($tag->getRevision() === null) return;
        /** @var TagRevision $oldRevision */
        $oldRevision = $this->revisionService->findByUuid($tag->getRevision());

        if($oldRevision->getHidden() != $newRevision->getHidden()) {
            $relations = $this->relationService->findByTag($tag->getUuid());

            foreach($relations as $relation) {
                /** @var PasswordRevision $passwordRevision */
                $passwordRevision = $this->passwordRevisionService->findByModel($relation->getPassword());
                $relation->setHidden($newRevision->isHidden() || $passwordRevision->isHidden());
                $this->relationService->save($relation);
            }
        }
    }

    /**
     * @param Tag $tag
     *
     * @throws \Exception
     */
    public function preDelete(Tag $tag): void {
        $relations = $this->relationService->findByTag($tag->getUuid());

        foreach($relations as $relation) {
            $this->relationService->delete($relation);
        }
    }

    /**
     * @param Tag $tag
     *
     * @throws \Exception
     */
    public function postDelete(Tag $tag): void {
        /** @var TagRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($tag->getUuid());

        foreach($revisions as $revision) {
            $this->revisionService->delete($revision);
        }
    }

    /**
     * @param Tag $originalTag
     * @param Tag $clonedTag
     *
     * @throws \Exception
     */
    public function postClone(Tag $originalTag, Tag $clonedTag): void {
        /** @var TagRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($originalTag->getUuid());

        $currentClonedRevision = null;
        foreach($revisions as $revision) {
            /** @var TagRevision $revisionClone */
            $revisionClone = $this->revisionService->clone($revision, ['model' => $clonedTag->getUuid()]);
            $this->revisionService->save($revisionClone);
            if($revision->getUuid() == $originalTag->getRevision()) {
                $clonedTag->setRevision($revisionClone->getUuid());
                $currentClonedRevision = $revisionClone;
            }
        }

        $relations = $this->relationService->findByTag($originalTag->getUuid());
        foreach($relations as $relation) {
            $relationClone = $this->relationService->clone($relation, [
                'tag'         => $currentClonedRevision->getModel(),
                'tagRevision' => $currentClonedRevision->getUuid(),
                'hidden'      => $currentClonedRevision->isHidden() || $relation->isHidden()
            ]);
            $this->relationService->save($relationClone);
        }
    }
}