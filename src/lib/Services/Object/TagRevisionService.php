<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\ValidationService;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class TagRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
class TagRevisionService extends AbstractRevisionService {

    /**
     * @var string
     */
    protected string $class = TagRevision::class;

    /**
     * TagRevisionService constructor.
     *
     * @param UuidHelper         $uuidHelper
     * @param IEventDispatcher   $eventDispatcher
     * @param EnvironmentService $environment
     * @param TagRevisionMapper  $revisionMapper
     * @param ValidationService  $validationService
     * @param EncryptionService  $encryption
     */
    public function __construct(
        UuidHelper $uuidHelper,
        IEventDispatcher $eventDispatcher,
        EnvironmentService $environment,
        TagRevisionMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryption
    ) {
        parent::__construct($uuidHelper, $eventDispatcher, $environment, $revisionMapper, $validationService, $encryption);
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
     * @throws ApiException
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

        $revision = $this->validation->validateTag($revision);
        $this->fireEvent('instantiated', $revision);

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
        if($this->userId !== null) $revision->setUserId($this->userId);
        $revision->setUuid($this->uuidHelper->generateUuid());
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->setDeleted(false);
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setCseKey($cseKey);
        $revision->setCseType($cseType);
        $revision->setHidden($hidden);
        $revision->setTrashed($trashed);
        $revision->setLabel($label);
        $revision->setColor($color);
        $revision->setEdited($edited);
        $revision->setFavorite($favorite);
        $revision->setSseType($this->getSseEncryption($cseType));
        $revision->setClient($this->environment->getClient());

        return $revision;
    }
}