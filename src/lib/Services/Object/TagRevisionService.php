<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\ValidationService;

/**
 * Class TagRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagRevisionService extends AbstractRevisionService {

    /**
     * @var string
     */
    protected $class = TagRevision::class;

    /**
     * TagRevisionService constructor.
     *
     * @param HookManager        $hookManager
     * @param EnvironmentService $environment
     * @param TagRevisionMapper  $revisionMapper
     * @param ValidationService  $validationService
     * @param EncryptionService  $encryptionService
     */
    public function __construct(
        HookManager $hookManager,
        EnvironmentService $environment,
        TagRevisionMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryptionService
    ) {
        parent::__construct($hookManager, $environment, $revisionMapper, $validationService, $encryptionService);
    }

    /**
     * @param string $model
     * @param string $label
     * @param string $color
     * @param string $cseKey
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $favorite
     *
     * @return TagRevision
     *
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function create(
        string $model,
        string $label,
        string $color,
        string $cseKey,
        string $cseType,
        int $edited,
        bool $hidden,
        bool $trashed,
        bool $favorite
    ): TagRevision {

        $revision = $this->createModel($model, $label, $color, $cseKey, $cseType, $edited, $hidden, $trashed, $favorite);

        $revision = $this->validationService->validateTag($revision);
        $this->hookManager->emit($this->class, 'postCreate', [$revision]);

        return $revision;
    }

    /**
     * @param string $model
     * @param string $label
     * @param string $color
     * @param string $cseKey
     * @param string $cseType
     * @param int    $edited
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $favorite
     *
     * @return TagRevision
     */
    protected function createModel(
        string $model,
        string $label,
        string $color,
        string $cseKey,
        string $cseType,
        int $edited,
        bool $hidden,
        bool $trashed,
        bool $favorite
    ): TagRevision {

        $revision = new TagRevision();
        $revision->setUserId($this->userId);
        $revision->setUuid($this->generateUuidV4());
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->setDeleted(false);
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setCseKey($cseKey);
        $revision->setCseType($cseType);
        $revision->setSseType(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $revision->setHidden($hidden);
        $revision->setTrashed($trashed);
        $revision->setLabel($label);
        $revision->setColor($color);
        $revision->setEdited($edited);
        $revision->setFavorite($favorite);

        return $revision;
    }
}