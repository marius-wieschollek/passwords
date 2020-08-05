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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;

/**
 * Class TagObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class TagObjectHelper extends AbstractObjectHelper {

    const LEVEL_PASSWORDS     = 'passwords';
    const LEVEL_PASSWORD_IDS  = 'password-ids';
    const LEVEL_PASSWORD_TAGS = 'password-tags';

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
     * @return array|null
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    public function getApiObject(
        EntityInterface $tag,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        $detailLevel = explode('+', $level);
        $withModel = in_array(self::LEVEL_MODEL, $detailLevel);

        /** @var TagRevision $revision */
        $revision = $this->getRevision($tag, $filter, $withModel);
        if($revision === null) return null;

        if($withModel) {
            $object = $this->getModel($tag, $revision);
        } else {
            $object = ['id' => $tag->getUuid()];
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($tag, $object);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $includeTags = in_array(self::LEVEL_PASSWORD_TAGS, $detailLevel);
            $object = $this->getPasswords($revision, $object, $includeTags);
        } else if(in_array(self::LEVEL_PASSWORD_IDS, $detailLevel)) {
            $object = $this->getPasswords($revision, $object, false, false);
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
            'id'       => $tag->getUuid(),
            'created'  => $tag->getCreated(),
            'updated'  => $tag->getUpdated(),
            'edited'   => $revision->getEdited(),
            'revision' => $tag->getRevision(),
            'label'    => $revision->getLabel(),
            'color'    => $revision->getColor(),
            'cseKey'   => $revision->getCseKey(),
            'cseType'  => $revision->getCseType(),
            'sseType'  => $revision->getSseType(),
            'hidden'   => $revision->isHidden(),
            'trashed'  => $revision->isTrashed(),
            'favorite' => $revision->isFavorite(),
            'client'   => $revision->getClient()
        ];
    }

    /**
     * @param Tag   $tag
     * @param array $object
     *
     * @return array
     * @throws Exception
     */
    protected function getRevisions(Tag $tag, array $object): array {
        /** @var TagRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($tag->getUuid(), true);

        $object['revisions'] = [];
        foreach($revisions as $revision) {
            $current = [
                'id'       => $revision->getUuid(),
                'created'  => $revision->getCreated(),
                'updated'  => $revision->getUpdated(),
                'edited'   => $revision->getEdited(),
                'label'    => $revision->getLabel(),
                'color'    => $revision->getColor(),
                'cseKey'   => $revision->getCseKey(),
                'cseType'  => $revision->getCseType(),
                'sseType'  => $revision->getSseType(),
                'hidden'   => $revision->isHidden(),
                'trashed'  => $revision->isTrashed(),
                'favorite' => $revision->isFavorite(),
                'client'   => $revision->getClient()
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param TagRevision $revision
     * @param array       $object
     * @param bool        $includeTags
     * @param bool        $includeModels
     *
     * @return array
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    protected function getPasswords(TagRevision $revision, array $object, bool $includeTags = false, bool $includeModels = true): array {

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $object['passwords'] = [];
        $objectHelper        = $this->getPasswordObjectHelper();
        $passwords           = $this->passwordService->findByTag($revision->getModel(), $revision->isHidden());

        $detailLevel = $includeTags ? self::LEVEL_MODEL.'+'.PasswordObjectHelper::LEVEL_TAGS:self::LEVEL_MODEL;
        foreach($passwords as $password) {
            if(!$revision->isTrashed() && $password->isSuspended()) continue;
            if($includeModels) {
                $obj = $objectHelper->getApiObject($password, $detailLevel, $filters);

                if($obj !== null) $object['passwords'][] = $obj;
            } else if($objectHelper->matchesFilter($password, $filters)) {
                $object['passwords'][] = $password->getUuid();
            }
        }

        return $object;
    }

    /**
     * @return PasswordObjectHelper
     * @throws QueryException
     */
    protected function getPasswordObjectHelper(): PasswordObjectHelper {
        if(!$this->passwordObjectHelper) {
            $this->passwordObjectHelper = $this->container->query(PasswordObjectHelper::class);
        }

        return $this->passwordObjectHelper;
    }
}