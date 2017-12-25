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

/**
 * Class TagService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagService extends AbstractModelService {

    /**
     * @var TagMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Tag::class;

    /**
     * @param string $passwordUuid
     * @param bool   $includeHidden
     *
     * @return Tag[]
     */
    public function findByPassword(string $passwordUuid, bool $includeHidden = false): array {
        return $this->mapper->getByPassword($passwordUuid, $includeHidden);
    }
}