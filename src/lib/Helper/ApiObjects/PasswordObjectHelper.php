<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 00:19
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\IAppContainer;

/**
 * Class PasswordObjectHelper
 *
 * @package OCA\Passwords\Helper
 */
class PasswordObjectHelper extends AbstractObjectHelper {

    const LEVEL_FOLDER = 'folder';
    const LEVEL_TAGS   = 'tags';

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var TagObjectHelper
     */
    protected $tagObjectHelper;

    /**
     * @var FolderObjectHelper
     */
    protected $folderObjectHelper;

    /**
     * PasswordApiController constructor.
     *
     * @param IAppContainer           $container
     * @param PasswordRevisionService $revisionService
     * @param TagService              $tagService
     * @param FolderService           $folderService
     */
    public function __construct(
        IAppContainer $container,
        TagService $tagService,
        FolderService $folderService,
        PasswordRevisionService $revisionService
    ) {
        parent::__construct($container);

        $this->tagService      = $tagService;
        $this->folderService   = $folderService;
        $this->revisionService = $revisionService;
    }

    /**
     * @param AbstractModelEntity|Password $password
     * @param string                       $level
     * @param bool                         $excludeHidden
     * @param bool                         $excludeTrash
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getApiObject(
        AbstractModelEntity $password,
        string $level = self::LEVEL_MODEL,
        bool $excludeHidden = true,
        bool $excludeTrash = false
    ): ?array {
        $detailLevel = explode('+', $level);
        /** @var PasswordRevision $revision */
        $revision = $this->revisionService->findByUuid($password->getRevision());

        if($excludeTrash && $revision->isTrashed()) return null;
        if($excludeHidden && $revision->isHidden()) return null;

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($password, $revision);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($password, $object);
        }
        if(in_array(self::LEVEL_FOLDER, $detailLevel)) {
            $object = $this->getFolder($revision, $object);
        }
        if(in_array(self::LEVEL_TAGS, $detailLevel)) {
            $object = $this->getTags($revision, $object);
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
            'id'        => $password->getUuid(),
            'owner'     => $password->getUserId(),
            'created'   => $password->getCreated(),
            'updated'   => $password->getUpdated(),
            'revision'  => $revision->getUuid(),
            'label'     => $revision->getLabel(),
            'username'  => $revision->getUsername(),
            'password'  => $revision->getPassword(),
            'notes'     => $revision->getNotes(),
            'url'       => $revision->getUrl(),
            'status'    => $revision->getStatus(),
            'hash'      => $revision->getHash(),
            'folder'    => $revision->getFolder(),
            'cseType'   => $revision->getCseType(),
            'sseType'   => $revision->getSseType(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite()
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
        $revisions = $this->revisionService->findByModel($password->getUuid());

        $object['revisions'] = [];
        foreach ($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
                'owner'     => $revision->getUserId(),
                'created'   => $revision->getCreated(),
                'updated'   => $revision->getUpdated(),
                'label'     => $revision->getLabel(),
                'username'  => $revision->getUsername(),
                'password'  => $revision->getPassword(),
                'notes'     => $revision->getNotes(),
                'url'       => $revision->getUrl(),
                'status'    => $revision->getStatus(),
                'hash'      => $revision->getHash(),
                'folder'    => $revision->getFolder(),
                'cseType'   => $revision->getCseType(),
                'sseType'   => $revision->getSseType(),
                'hidden'    => $revision->isHidden(),
                'trashed'   => $revision->isTrashed(),
                'favourite' => $revision->isFavourite(),
            ];

            $object['revisions'][] = $current;
        }

        return $object;
    }

    /**
     * @param PasswordRevision $revision
     * @param array            $object
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getTags(PasswordRevision $revision, array $object): array {
        $object['tags'] = [];
        $objectHelper   = $this->getTagObjectHelper();
        $tags           = $this->tagService->findByPassword($revision->getModel(), $revision->isHidden());
        foreach ($tags as $tag) {
            $obj = $objectHelper->getApiObject($tag, self::LEVEL_MODEL, !$revision->isHidden(), !$revision->isTrashed());

            if($obj !== null) $object['tags'][] = $obj;
        }

        return $object;
    }

    /**
     * @param PasswordRevision $revision
     * @param array            $object
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getFolder(PasswordRevision $revision, array $object): array {
        $object['folder'] = [];

        $objectHelper = $this->getFolderObjectHelper();
        $folder       = $this->folderService->findByUuid($revision->getFolder());
        $obj          = $objectHelper->getApiObject($folder, self::LEVEL_MODEL, !$revision->isHidden(), !$revision->isTrashed());

        if($obj !== null) {
            $object['folder'] = $obj;
        } else {
            $folder           = $this->folderService->getBaseFolder();
            $object['folder'] = $objectHelper->getApiObject($folder);
        }

        return $object;
    }

    /**
     * @return TagObjectHelper
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getTagObjectHelper(): TagObjectHelper {
        if(!$this->tagObjectHelper) {
            $this->tagObjectHelper = $this->container->query('TagObjectHelper');
        }

        return $this->tagObjectHelper;
    }

    /**
     * @return FolderObjectHelper
     * @throws \OCP\AppFramework\QueryException
     */
    protected function getFolderObjectHelper(): FolderObjectHelper {
        if(!$this->folderObjectHelper) {
            $this->folderObjectHelper = $this->container->query('FolderObjectHelper');
        }

        return $this->folderObjectHelper;
    }
}