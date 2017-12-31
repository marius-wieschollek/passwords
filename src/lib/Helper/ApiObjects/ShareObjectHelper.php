<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 18:33
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\ShareRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\ShareRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\IAppContainer;

class ShareObjectHelper extends AbstractObjectHelper {

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * ShareObjectHelper constructor.
     *
     * @param IAppContainer        $container
     * @param ShareService         $shareService
     * @param EncryptionService    $encryptionService
     * @param ShareRevisionService $revisionService
     */
    public function __construct(
        IAppContainer $container,
        ShareService $shareService,
        EncryptionService $encryptionService,
        ShareRevisionService $revisionService
    ) {
        parent::__construct($container, $encryptionService, $revisionService);
        $this->shareService    = $shareService;
    }

    /**
     * @param ModelInterface|Share $share
     * @param string               $level
     * @param array                $filter
     *
     * @return array|null
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getApiObject(
        ModelInterface $share,
        string $level = self::LEVEL_MODEL,
        $filter = []
    ): ?array {
        /** @var ShareRevision $revision */
        $revision = $this->getRevision($share, $filter);
        if($revision === null) return null;

        $detailLevel = explode('+', $level);

        return [];
    }
}