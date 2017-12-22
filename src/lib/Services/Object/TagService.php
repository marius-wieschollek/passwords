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
use OCP\IUser;

/**
 * Class TagService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagService extends AbstractService {

    /**
     * @var IUser
     */
    protected $user;

    /**
     * @var TagMapper
     */
    protected $tagMapper;

    /**
     * TagService constructor.
     *
     * @param IUser     $user
     * @param TagMapper $tagMapper
     */
    public function __construct(
        IUser $user,
        TagMapper $tagMapper
    ) {
        $this->user      = $user;
        $this->tagMapper = $tagMapper;
    }

    /**
     * @return Tag[]
     * @throws \Exception
     */
    public function getAllTags(): array {
        /** @var Tag[] $tags */
        return $this->tagMapper->findAll();
    }

    /**
     * @param int $tagId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Tag
     *
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getTagById(int $tagId): Tag {
        return $this->tagMapper->findById($tagId);
    }

    /**
     * @param string $tagId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Tag
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getTagByUuid(string $tagId): Tag {
        return $this->tagMapper->findByUuid($tagId);
    }

    /**
     * @param string $revisionUuid
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
    public function destroyTag(Tag $tag): void {
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
        $model->setUuid($this->generateUuidV4());
        $model->setRevision($revisionUuid);
        $model->setDeleted(false);
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }
}