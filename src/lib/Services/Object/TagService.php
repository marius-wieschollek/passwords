<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 19:42
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Tag;

/**
 * Class TagService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagService extends AbstractModelService {

    /**
     * @var string
     */
    protected $class = Tag::class;
}