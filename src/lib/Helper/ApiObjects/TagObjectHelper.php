<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\IAppContainer;

/**
 * Class TagObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class TagObjectHelper extends AbstractObjectHelper {

    const LEVEL_PASSWORDS = 'passwords';

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var PasswordObjectHelper
     */
    protected $passwordObjectHelper;

    /**
     * TagObjectHelper constructor.
     *
     * @param IAppContainer      $container
     * @param TagService         $tagService
     * @param PasswordService    $passwordService
     * @param TagRevisionService $revisionService
     * @param EncryptionService  $encryptionService
     */
    public function __construct(
        IAppContainer $container,
        TagService $tagService,
        PasswordService $passwordService,
        TagRevisionService $revisionService,
        EncryptionService $encryptionService
    ) {
        parent::__construct($container, $encryptionService, $revisionService);

        $this->tagService      = $tagService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param EntityInterface|Tag $tag
     * @param string              $level
     * @param array               $filter
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getApiObject(
        EntityInterface $tag,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        /** @var TagRevision $revision */
        $revision = $this->getRevision($tag, $filter);
        if($revision === null) return null;

        $detailLevel = explode('+', $level);
        $object      = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($tag, $revision);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($revision, $object);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($tag, $object);
        }

        return $object;
    }

    /**
     * @param Tag         $tag
     * @param TagRevision $revision
     *
     * @return array
     */
    protected function getModel(Tag $tag, TagRevision $revision): array {

        return [
            'id'        => $tag->getUuid(),
            'created'   => $tag->getCreated(),
            'updated'   => $tag->getUpdated(),
            'edited'    => $revision->getEdited(),
            'revision'  => $tag->getRevision(),
            'label'     => $revision->getLabel(),
            'color'     => $revision->getColor(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite()
        ];
    }

    /**
     * @param Tag   $tag
     * @param array $object
     *
     * @return array
     * @throws \Exception
     */
    protected function getRevisions(Tag $tag, array $object): array {
        /** @var TagRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($tag->getUuid(), true);

        $object['revisions'] = [];
        foreach($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
                'created'   => $revision->getCreated(),
                'updated'   => $revision->getUpdated(),
                'edited'    => $revision->getEdited(),
                'label'     => $revision->getLabel(),
                'color'     => $revision->getColor(),
                'hidden'    => $revision->isHidden(),
                'trashed'   => $revision->isTrashed(),
                'favourite' => $revision->isFavourite()
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param TagRevision $revision
     * @param array       $object
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswords(TagRevision $revision, array $object): array {

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $object['passwords'] = [];
        $objectHelper        = $this->getPasswordObjectHelper();
        $passwords           = $this->passwordService->findByTag($revision->getModel());

        foreach($passwords as $password) {
            if(!$revision->isTrashed() && $password->isSuspended()) continue;
            $obj = $objectHelper->getApiObject($password, self::LEVEL_MODEL, $filters);

            if($obj !== null) $object['passwords'][] = $obj;
        }

        return $object;
    }

    /**
     * @return PasswordObjectHelper
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getPasswordObjectHelper(): PasswordObjectHelper {
        if(!$this->passwordObjectHelper) {
            $this->passwordObjectHelper = $this->container->query(PasswordObjectHelper::class);
        }

        return $this->passwordObjectHelper;
    }
}