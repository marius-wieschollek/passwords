<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:47
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;

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
     * @var TagRevisionService
     */
    protected $revisionService;

    /**
     * TagObjectHelper constructor.
     *
     * @param TagService         $tagService
     * @param TagRevisionService $revisionService
     */
    public function __construct(TagService $tagService, TagRevisionService $revisionService) {
        $this->tagService = $tagService;
        $this->revisionService = $revisionService;
    }

    /**
     * @param AbstractModelEntity|Tag $tag
     * @param string                  $level
     *
     * @return array
     * @throws Exception
     */
    public function getApiObject(AbstractModelEntity $tag, string $level = self::LEVEL_MODEL): array {
        $detailLevel = explode('+', $level);
        /** @var TagRevision $revision */
        $revision = $this->revisionService->findByUuid($tag->getRevision());

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($tag, $revision);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($tag, $object);
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
            'owner'     => $tag->getUserId(),
            'created'   => $tag->getCreated(),
            'updated'   => $tag->getUpdated(),
            'revision'  => $tag->getRevision(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite(),
            'label'      => $revision->getLabel(),
            'color'     => $revision->getColor()
        ];
    }

    /**
     * @param Tag   $tag
     * @param array $object
     *
     * @return array
     */
    protected function getRelations(Tag $tag, array $object): array {

        $object['passwords'] = [];

        return $object;
    }

    /**
     * @param       $tag
     * @param array $object
     *
     * @return array
     */
    protected function getPasswords(Tag $tag, array $object): array {

        $object['passwords'] = [];

        return $object;
    }
}