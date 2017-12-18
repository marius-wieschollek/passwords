<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:42
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCP\IUser;

/**
 * Class TagService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagService {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;
    /**
     * @var TagMapper
     */
    private $tagMapper;

    /**
     * TagService constructor.
     *
     * @param IUser             $user
     * @param TagMapper         $tagMapper
     * @param ValidationService $validationService
     * @param EncryptionService $encryptionService
     */
    public function __construct(
        IUser $user,
        TagMapper $tagMapper,
        ValidationService $validationService,
        EncryptionService $encryptionService
    ) {
        $this->user              = $user;
        $this->tagMapper         = $tagMapper;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @return Tag[]
     */
    public function getAllTags() {
        /** @var Tag[] $tags */
        $tags = $this->tagMapper->findAll();

        foreach($tags as $tag) $this->encryptionService->decryptTag($tag);

        return $tags;
    }

    /**
     * @param int $tagId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Tag
     */
    public function getTagById(int $tagId) {
        /** @var Tag $tag */
        $tag = $this->tagMapper->findById($tagId);
        return $this->encryptionService->decryptTag($tag);
    }

    /**
     * @param string $tagId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Tag
     */
    public function getTagByUuid(string $tagId): Tag {
        /** @var Tag $tag */
        $tag = $this->tagMapper->findByUuid($tagId);
        return $this->encryptionService->decryptTag($tag);
    }

    /**
     * @param string $name
     * @param string $color
     * @param string $cseType
     * @param string $sseType
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return Tag
     */
    public function createTag(string $revisionUuid): Tag {
        return $this->createTagModel($revisionUuid);
    }

    /**
     * @param Tag $tag
     *
     * @return Tag|\OCP\AppFramework\Db\Entity
     */
    public function saveTag(Tag $tag): Tag {
        $tag = $this->encryptionService->encryptTag($tag);

        if(empty($tag->getId())) {
            return $this->tagMapper->insert($tag);
        } else {
            $tag->setUpdated(time());

            return $this->tagMapper->update($tag);
        }
    }

    /**
     * @param Tag $tag
     */
    public function destroyTag(Tag $tag) {
        $this->tagMapper->delete($tag);
    }

    /**
     * @param string $revisionUuid
     *
     * @return Tag
     *
     */
    protected function createTagModel(string $revisionUuid): Tag {
        $model = new Tag();
        $model->setUserId($this->user->getUID());
        $model->setUuid($this->tagMapper->generateUuidV4());
        $model->setRevision($revisionUuid);
        $model->setDeleted(false);
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }
}