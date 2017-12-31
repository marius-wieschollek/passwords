<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 16:26
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\ModelInterface;
use OCP\AppFramework\IAppContainer;

/**
 * Class AbstractObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
abstract class AbstractObjectHelper {

    const LEVEL_MODEL     = 'model';
    const LEVEL_REVISIONS = 'revisions';

    /**
     * @var IAppContainer
     */
    protected $container;

    /**
     * AbstractObjectHelper constructor.
     *
     * @param IAppContainer $container
     */
    public function __construct(IAppContainer $container) {
        $this->container = $container;
    }

    /**
     * @param ModelInterface $model
     * @param string              $level
     * @param bool                $excludeHidden
     * @param bool                $excludeTrash
     *
     * @return array|null
     */
    abstract public function getApiObject(
        ModelInterface $model,
        string $level = self::LEVEL_MODEL,
        bool $excludeHidden = true,
        bool $excludeTrash = false
    ): ?array;
}