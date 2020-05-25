<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;

/**
 * Class PasswordObjectHelper
 *
 * @package OCA\Passwords\Helper
 */
class PasswordObjectHelper extends AbstractObjectHelper {

    const LEVEL_SHARES  = 'shares';
    const LEVEL_FOLDER  = 'folder';
    const LEVEL_TAGS    = 'tags';
    const LEVEL_TAG_IDS = 'tag-ids';

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var TagObjectHelper
     */
    protected $tagObjectHelper;

    /**
     * @var ShareObjectHelper
     */
    protected $shareObjectHelper;

    /**
     * @var FolderObjectHelper
     */
    protected $folderObjectHelper;

    /**
     * PasswordObjectHelper constructor.
     *
     * @param IAppContainer           $container
     * @param TagService              $tagService
     * @param ShareService            $shareService
     * @param FolderService           $folderService
     * @param EncryptionService       $encryptionService
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(
        IAppContainer $container,
        TagService $tagService,
        ShareService $shareService,
        FolderService $folderService,
        EncryptionService $encryptionService,
        PasswordRevisionService $revisionService
    ) {
        parent::__construct($container, $encryptionService, $revisionService);

        $this->tagService    = $tagService;
        $this->shareService  = $shareService;
        $this->folderService = $folderService;
    }

    /**
     * @param EntityInterface|Password $password
     * @param string                   $level
     * @param array                    $filter
     *
     * @return array|null
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    public function getApiObject(
        EntityInterface $password,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        $detailLevel = explode('+', $level);
        $withModel = in_array(self::LEVEL_MODEL, $detailLevel);

        /** @var PasswordRevision $revision */
        $revision = $this->getRevision($password, $filter, $withModel);
        if($revision === null) return null;

        if($withModel) {
            $object = $this->getModel($password, $revision);
        } else {
            $object = ['id' => $password->getUuid()];
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($password, $object);
        }
        if(in_array(self::LEVEL_FOLDER, $detailLevel)) {
            $object = $this->getFolder($revision, $object);
        }
        if(in_array(self::LEVEL_TAGS, $detailLevel)) {
            $object = $this->getTags($revision, $object);
        } else if(in_array(self::LEVEL_TAG_IDS, $detailLevel)) {
            $object = $this->getTags($revision, $object, false);
        }
        if(in_array(self::LEVEL_SHARES, $detailLevel)) {
            $object = $this->getShares($revision, $object);
        }

        return $object;
    }

    /**
     * @param Password         $password
     * @param PasswordRevision $revision
     *
     * @return array
     */
    protected function getModel(Password $password, PasswordRevision $revision): array {
        return [
            'id'           => $password->getUuid(),
            'created'      => $password->getCreated(),
            'updated'      => $password->getUpdated(),
            'edited'       => $revision->getEdited(),
            'share'        => $password->getShareId(),
            'shared'       => $password->hasShares(),
            'revision'     => $revision->getUuid(),
            'label'        => $revision->getLabel(),
            'username'     => $revision->getUsername(),
            'password'     => $revision->getPassword(),
            'notes'        => $revision->getNotes(),
            'customFields' => $revision->getCustomFields(),
            'url'          => $revision->getUrl(),
            'status'       => $revision->getStatus(),
            'statusCode'   => $revision->getStatusCode(),
            'hash'         => $revision->getHash(),
            'folder'       => $revision->getFolder(),
            'cseKey'       => $revision->getCseKey(),
            'cseType'      => $revision->getCseType(),
            'sseType'      => $revision->getSseType(),
            'hidden'       => $revision->isHidden(),
            'trashed'      => $revision->isTrashed(),
            'favorite'     => $revision->isFavorite(),
            'editable'     => $password->isEditable(),
            'client'       => $revision->getClient()
        ];
    }

    /**
     * @param Password $password
     * @param array    $object
     *
     * @return array
     * @throws Exception
     */
    protected function getRevisions(Password $password, array $object): array {
        /** @var PasswordRevision[] $revisions */
        $revisions = $this->revisionService->findByModel($password->getUuid(), true);

        $object['revisions'] = [];
        foreach($revisions as $revision) {
            $current = [
                'id'           => $revision->getUuid(),
                'created'      => $revision->getCreated(),
                'updated'      => $revision->getUpdated(),
                'edited'       => $revision->getEdited(),
                'label'        => $revision->getLabel(),
                'username'     => $revision->getUsername(),
                'password'     => $revision->getPassword(),
                'notes'        => $revision->getNotes(),
                'customFields' => $revision->getCustomFields(),
                'url'          => $revision->getUrl(),
                'status'       => $revision->getStatus(),
                'statusCode'   => $revision->getStatusCode(),
                'hash'         => $revision->getHash(),
                'folder'       => $revision->getFolder(),
                'cseKey'       => $revision->getCseKey(),
                'cseType'      => $revision->getCseType(),
                'sseType'      => $revision->getSseType(),
                'hidden'       => $revision->isHidden(),
                'trashed'      => $revision->isTrashed(),
                'favorite'     => $revision->isFavorite(),
                'client'       => $revision->getClient()
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param PasswordRevision $revision
     * @param array            $object
     * @param bool             $includeModels
     *
     * @return array
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    protected function getTags(PasswordRevision $revision, array $object, bool $includeModels = true): array {
        $object['tags'] = [];

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $objectHelper = $this->getTagObjectHelper();
        $tags         = $this->tagService->findByPassword($revision->getModel(), $revision->isHidden());
        foreach($tags as $tag) {
            if($includeModels) {
                $obj = $objectHelper->getApiObject($tag, self::LEVEL_MODEL, $filters);

                if($obj !== null) $object['tags'][] = $obj;
            } else if($objectHelper->matchesFilter($tag, $filters)) {
                $object['tags'][] = $tag->getUuid();
            }
        }

        return $object;
    }

    /**
     * @param PasswordRevision $revision
     * @param array            $object
     *
     * @return array
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws QueryException
     */
    protected function getFolder(PasswordRevision $revision, array $object): array {
        $object['folder'] = [];

        $filters      = $revision->isHidden() ? []:['hidden' => false];
        $objectHelper = $this->getFolderObjectHelper();
        $folder       = $this->folderService->findByUuid($revision->getFolder());
        $obj          = $objectHelper->getApiObject($folder, self::LEVEL_MODEL, $filters);

        if($obj !== null) {
            $object['folder'] = $obj;
        } else {
            $folder           = $this->folderService->getBaseFolder();
            $object['folder'] = $objectHelper->getApiObject($folder);
        }

        return $object;
    }

    /**
     * @param PasswordRevision $revision
     * @param                  $object
     *
     * @return array
     * @throws Exception
     */
    protected function getShares(PasswordRevision $revision, $object): array {
        $objectHelper = $this->getShareObjectHelper();

        $object['shares'] = [];
        $shares           = $this->shareService->findBySourcePassword($revision->getModel());
        foreach($shares as $share) {
            $object['shares'][] = $objectHelper->getApiObject($share);

            usort($object['shares'], function($a, $b) {
                return $a['receiver'] > $b['receiver'] ? 1:-1;
            });
        }

        if(isset($object['share']) && $object['share']) {
            $share           = $this->shareService->findByUuid($object['share']);
            $object['share'] = $objectHelper->getApiObject($share);
        }

        return $object;
    }

    /**
     * @return TagObjectHelper
     * @throws QueryException
     */
    protected function getTagObjectHelper(): TagObjectHelper {
        if(!$this->tagObjectHelper) {
            $this->tagObjectHelper = $this->container->query(TagObjectHelper::class);
        }

        return $this->tagObjectHelper;
    }

    /**
     * @return FolderObjectHelper
     * @throws QueryException
     */
    protected function getFolderObjectHelper(): FolderObjectHelper {
        if(!$this->folderObjectHelper) {
            $this->folderObjectHelper = $this->container->query(FolderObjectHelper::class);
        }

        return $this->folderObjectHelper;
    }

    /**
     * @return ShareObjectHelper
     * @throws QueryException
     */
    protected function getShareObjectHelper(): ShareObjectHelper {
        if(!$this->shareObjectHelper) {
            $this->shareObjectHelper = $this->container->query(ShareObjectHelper::class);
        }

        return $this->shareObjectHelper;
    }
}