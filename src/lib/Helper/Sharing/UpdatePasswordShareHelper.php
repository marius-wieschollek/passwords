<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Sharing;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Exception\ApiException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class UpdatePasswordShareHelper
 *
 * @package OCA\Passwords\Helper\Sharing
 */
class UpdatePasswordShareHelper extends CreatePasswordShareHelper {

    /**
     * @param Share    $share
     * @param int|null $expires
     * @param bool     $editable
     * @param bool     $shareable
     *
     * @return Share
     * @throws ApiException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function updatePasswordShare(Share $share, ?int $expires, bool $editable, bool $shareable): Share {
        /** @var Password $model */
        $model = $this->passwordModelService->findByUuid($share->getSourcePassword());
        if($model->getShareId()) [$editable, $expires] = $this->checkSourceShare($share->getSourcePassword(), $model, $editable, $expires);

        $share->setExpires($expires);
        $share->setEditable($editable);
        $share->setShareable($shareable);
        $share->setSourceUpdated(true);
        return $this->modelService->save($share);
    }
}