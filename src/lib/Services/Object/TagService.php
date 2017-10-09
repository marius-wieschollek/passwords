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
     * @param array $search
     *
     * @return Tag[]
     */
    public function findTags(array $search = []) {
        return $this->tagMapper->findMatching(
            $search
        );
    }

    /**
     * @param int $tagId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Tag
     */
    public function getTagById(int $tagId) {
        return $this->tagMapper->findById(
            $tagId
        );
    }

    /**
     * @param string $tagId
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Tag
     */
    public function getTagByUuid(string $tagId): Tag {
        return $this->tagMapper->findByUuid(
            $tagId
        );
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
    public function createTag(
        string $name,
        string $color,
        string $cseType,
        string $sseType,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): Tag {
        $model = $this->createTagModel($name, $color, $cseType, $sseType, $hidden, $trashed, $deleted, $favourite);

        $model = $this->validationService->validateTag($model);

        return $model;
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
    protected function createTagModel(
        string $name,
        string $color,
        string $cseType,
        string $sseType,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): Tag {
        $model = new Tag();
        $model->setUser($this->user->getUID());
        $model->setUuid($this->tagMapper->generateUuidV4());
        $model->setHidden($hidden);
        $model->setTrashed($trashed);
        $model->setDeleted($deleted);
        $model->setFavourite($favourite);
        $model->setName($name);
        $model->setColor($color);
        $model->setCseType($cseType);
        $model->setSseType($sseType);
        $model->setCreated(time());
        $model->setUpdated(time());

        return $model;
    }
}