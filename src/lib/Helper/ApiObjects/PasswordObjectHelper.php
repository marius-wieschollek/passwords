<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 00:19
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\IAppContainer;
use OCP\IUserManager;

/**
 * Class PasswordObjectHelper
 *
 * @package OCA\Passwords\Helper
 */
class PasswordObjectHelper extends AbstractObjectHelper {

    const LEVEL_SHARES = 'shares';
    const LEVEL_FOLDER = 'folder';
    const LEVEL_TAGS   = 'tags';

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var IUserManager
     */
    protected $userManager;

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
     * @var FolderObjectHelper
     */
    protected $folderObjectHelper;

    /**
     * PasswordApiController constructor.
     *
     * @param IAppContainer           $container
     * @param TagService              $tagService
     * @param IUserManager            $userManager
     * @param ShareService            $shareService
     * @param FolderService           $folderService
     * @param EncryptionService       $encryptionService
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(
        IAppContainer $container,
        TagService $tagService,
        IUserManager $userManager,
        ShareService $shareService,
        FolderService $folderService,
        EncryptionService $encryptionService,
        PasswordRevisionService $revisionService
    ) {
        parent::__construct($container, $encryptionService, $revisionService);

        $this->tagService    = $tagService;
        $this->userManager   = $userManager;
        $this->shareService  = $shareService;
        $this->folderService = $folderService;
    }

    /**
     * @param ModelInterface|Password $password
     * @param string                  $level
     * @param array                   $filter
     *
     * @return array|null
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getApiObject(
        ModelInterface $password,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        /** @var PasswordRevision $revision */
        $revision = $this->getRevision($password, $filter);
        if($revision === null) return null;

        $detailLevel = explode('+', $level);
        $object      = [];
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
            'id'        => $password->getUuid(),
            'created'   => $password->getCreated(),
            'updated'   => $password->getUpdated(),
            'share'     => $password->getShareId(),
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
            'favourite' => $revision->isFavourite(),
            'editable'  => $password->isEditable()
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
        foreach ($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
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

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

        $objectHelper = $this->getTagObjectHelper();
        $tags         = $this->tagService->findByPassword($revision->getModel(), $revision->isHidden());
        foreach ($tags as $tag) {
            $obj = $objectHelper->getApiObject($tag, self::LEVEL_MODEL, $filters);

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

        $filters = [];
        if(!$revision->isHidden()) $filters['hidden'] = false;
        if(!$revision->isTrashed()) $filters['trashed'] = false;

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
        $object['shares'] = [];
        $shares           = $this->shareService->findBySourcePassword($revision->getModel());
        foreach ($shares as $share) {
            $object['shares'][] = $this->formatShare($share);
        }

        if($object['share']) {
            $share           = $this->shareService->findByUuid($object['share']);
            $object['share'] = $this->formatShare($share);
        }

        return $object;
    }

    /**
     * @param Share $share
     *
     * @return array
     */
    protected function formatShare(Share $share): array {
        $owner    = $this->userManager->get($share->getUserId());
        $receiver = $this->userManager->get($share->getReceiver());

        return [
            'id'            => $share->getUuid(),
            'created'       => $share->getCreated(),
            'updated'       => $share->getUpdated(),
            'expires'       => $share->getExpires(),
            'editable'      => $share->isEditable(),
            'shareable'     => $share->isShareable(),
            'updatePending' => $share->isSourceUpdated() || $share->isTargetUpdated(),
            'owner'         => [
                'id'   => $owner->getUID(),
                'name' => $owner->getDisplayName()
            ],
            'receiver'      => [
                'id'   => $receiver->getUID(),
                'name' => $receiver->getDisplayName()
            ]
        ];
    }

    /**
     * @param ModelInterface|Password $model
     * @param array          $filters
     *
     * @return null|RevisionInterface
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function getRevision(ModelInterface $model, array $filters): ?RevisionInterface {
        if(isset($filters['sharedWithMe'])) {
            if($filters['sharedWithMe'] !== !empty($model->getShareId())) return null;
            unset($filters['sharedWithMe']);
        }

        if(isset($filters['sharedByMe'])) {
            if($filters['sharedByMe'] !== $model->hasShares()) return null;
            unset($filters['sharedByMe']);
        }

        return parent::getRevision($model, $filters);
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