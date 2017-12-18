<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:47
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Services\Object\TagService;

/**
 * Class TagObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class TagObjectHelper {

    const LEVEL_MODEL     = 'default';
    const LEVEL_RELATIONS = 'relations';
    const LEVEL_PASSWORDS = 'passwords';

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * TagObjectHelper constructor.
     *
     * @param TagService $tagService
     */
    public function __construct(TagService $tagService) {
        $this->tagService = $tagService;
    }

    /**
     * @param Tag $tag
     * @param string      $level
     *
     * @return array
     * @throws Exception
     */
    public function getApiObject(Tag $tag, string $level = self::LEVEL_MODEL): array {
        $detailLevel = explode('+', $level);

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($tag);
        }
        if(in_array(self::LEVEL_RELATIONS, $detailLevel)) {
            $object = $this->getRelations($tag, $object);
        }
        if(in_array(self::LEVEL_PASSWORDS, $detailLevel)) {
            $object = $this->getPasswords($tag, $object);
        }

        return $object;
    }

    /**
     * @param Tag $tag
     *
     * @return array
     */
    protected function getModel(Tag $tag): array {

        return [
            'id'        => $tag->getUuid(),
            'owner'     => $tag->getUserId(),
            'created'   => $tag->getCreated(),
            'updated'   => $tag->getUpdated(),
            'revision'   => $tag->getRevision(),
            'hidden'    => $tag->getHidden(),
            'trashed'   => $tag->getTrashed(),
            'favourite' => $tag->getFavourite(),
            'name'      => $tag->getName(),
            'color'     => $tag->getColor()
        ];
    }

    /**
     * @param Tag $tag
     * @param array       $object
     *
     * @return array
     */
    protected function getRelations(Tag $tag, array $object): array {

        $object['passwords'] = [];

        return $object;
    }

    /**
     * @param $tag
     * @param array $object
     *
     * @return array
     */
    protected function getPasswords(Tag $tag, array $object): array {

        $object['passwords'] = [];

        return $object;
    }
}