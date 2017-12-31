<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 18:33
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Services\Object\ShareRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\IAppContainer;

class ShareObjectHelper extends AbstractObjectHelper {

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var ShareRevisionService
     */
    protected $revisionService;

    /**
     * ShareObjectHelper constructor.
     *
     * @param IAppContainer        $container
     * @param ShareService         $shareService
     * @param ShareRevisionService $revisionService
     */
    public function __construct(IAppContainer $container, ShareService $shareService, ShareRevisionService $revisionService) {
        parent::__construct($container);
        $this->shareService = $shareService;
        $this->revisionService = $revisionService;
    }

    /**
     * @param ModelInterface $model
     * @param string              $level
     * @param bool                $excludeHidden
     * @param bool                $excludeTrash
     *
     * @return array|null
     */
    public function getApiObject(
        ModelInterface $model,
        string $level = self::LEVEL_MODEL,
        bool $excludeHidden = true,
        bool $excludeTrash = false
    ): ?array {
        // TODO: Implement getApiObject() method.
    }
}