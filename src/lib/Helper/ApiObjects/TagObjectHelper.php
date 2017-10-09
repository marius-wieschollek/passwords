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

    const LEVEL_DEFAULT = 'default';
    const LEVEL_DETAILS = 'details';

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
     * @param Tag    $tag
     * @param string $level
     *
     * @return array
     * @throws Exception
     */
    public function getApiObject(Tag $tag, string $level = self::LEVEL_DEFAULT): array {
        switch ($level) {
            case self::LEVEL_DEFAULT:
                return $this->getDefaultTagObject($tag);
                break;
            case self::LEVEL_DETAILS:
                return $this->getDetailedTagObject($tag);
                break;
        }

        throw new Exception('Invalid information detail level');
    }

    /**
     * @param Tag $tag
     *
     * @return array
     */
    protected function getDefaultTagObject(Tag $tag): array {

        return [
            'id'        => $tag->getUuid(),
            'owner'     => $tag->getUser(),
            'created'   => $tag->getCreated(),
            'updated'   => $tag->getUpdated(),
            'hidden'    => $tag->getHidden(),
            'trashed'   => $tag->getTrashed(),
            'name'      => $tag->getName(),
            'color'     => $tag->getColor(),
            'passwords' => []
        ];
    }

    /**
     * @param $tag
     *
     * @return array
     */
    protected function getDetailedTagObject($tag): array {

        return $this->getDefaultTagObject($tag);
    }
}