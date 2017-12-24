<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 12:18
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\TagRevision;

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
     * @param string $model
     * @param string $cseType
     * @param string $sseType
     * @param string $label
     * @param string $color
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return TagRevision
     *
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function create(
        string $model,
        string $cseType,
        string $sseType,
        string $label,
        string $color,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): TagRevision {

        $revision = $this->createModel($model, $cseType, $sseType, $label, $color, $hidden, $trashed, $deleted, $favourite);

        $revision = $this->validationService->validateTag($revision);

        return $revision;
    }

    /**
     * @param string $model
     * @param string $cseType
     * @param string $sseType
     * @param string $label
     * @param string $color
     * @param bool   $hidden
     * @param bool   $trashed
     * @param bool   $deleted
     * @param bool   $favourite
     *
     * @return TagRevision
     */
    protected function createModel(
        string $model,
        string $cseType,
        string $sseType,
        string $label,
        string $color,
        bool $hidden,
        bool $trashed,
        bool $deleted,
        bool $favourite
    ): TagRevision {

        $revision = new TagRevision();
        $revision->setDeleted(0);
        $revision->setUserId($this->user->getUID());
        $revision->setUuid($this->generateUuidV4());
        $revision->setCreated(time());
        $revision->setUpdated(time());
        $revision->setSseKey('');
        $revision->_setDecrypted(true);

        $revision->setModel($model);
        $revision->setCseType($cseType);
        $revision->setSseType($sseType);
        $revision->setHidden($hidden);
        $revision->setDeleted($deleted);
        $revision->setTrashed($trashed);
        $revision->setLabel($label);
        $revision->setColor($color);
        $revision->setFavourite($favourite);

        return $revision;
    }
}