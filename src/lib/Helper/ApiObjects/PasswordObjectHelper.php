<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 00:19
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\Object\PasswordRevisionService;

/**
 * Class PasswordObjectHelper
 *
 * @package OCA\Passwords\Helper
 */
class PasswordObjectHelper extends AbstractObjectHelper {

    const LEVEL_FOLDER = 'folder';
    const LEVEL_TAGS   = 'tags';

    /**
     * @var PasswordRevisionService
     */
    protected $revisionService;

    /**
     * PasswordApiController constructor.
     *
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(PasswordRevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param AbstractModelEntity|Password $password
     * @param string                       $level
     *
     * @return array
     * @throws Exception
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function getApiObject(AbstractModelEntity $password, string $level = self::LEVEL_MODEL): array {

        $detailLevel = explode('+', $level);
        /** @var PasswordRevision $revision */
        $revision = $this->revisionService->findByUuid($password->getRevision());

        $object = [];
        if(in_array(self::LEVEL_MODEL, $detailLevel)) {
            $object = $this->getModel($password, $revision);
        }
        if(in_array(self::LEVEL_REVISIONS, $detailLevel)) {
            $object = $this->getRevisions($password, $object);
        }
        if(in_array(self::LEVEL_FOLDER, $detailLevel)) {
            //$object = $this->getFolders($password, $object);
        }
        if(in_array(self::LEVEL_TAGS, $detailLevel)) {
            //$object = $this->getTags($password, $object);
        }

        return $object;
    }

    /**
     * @param Password         $password
     * @param PasswordRevision $revision
     *
     * @return array
     */
    protected function getModel(Password $password, PasswordRevision $revision): array {
        return [
            'id'        => $password->getUuid(),
            'owner'     => $password->getUserId(),
            'created'   => $password->getCreated(),
            'updated'   => $password->getUpdated(),
            'revision'  => $revision->getUuid(),
            'label'     => $revision->getLabel(),
            'username'  => $revision->getUsername(),
            'password'  => $revision->getPassword(),
            'notes'     => $revision->getNotes(),
            'url'       => $revision->getUrl(),
            'status'    => $revision->getStatus(),
            'hash'      => $revision->getHash(),
            'folder'    => $revision->getFolder(),
            'cseType'   => $revision->getCseType(),
            'sseType'   => $revision->getSseType(),
            'hidden'    => $revision->isHidden(),
            'trashed'   => $revision->isTrashed(),
            'favourite' => $revision->isFavourite()
        ];
    }

    /**
     * @param Password $password
     * @param array    $object
     *
     * @return array
     */
    protected function getRevisions(Password $password, array $object): array {
        $revisions = $this->revisionService->findByModel($password);

        $object['revisions'] = [];
        foreach ($revisions as $revision) {
            $current = [
                'id'        => $revision->getUuid(),
                'owner'     => $revision->getUserId(),
                'created'   => $revision->getCreated(),
                'updated'   => $revision->getUpdated(),
                'title'     => $revision->getLabel(),
                'login'     => $revision->getUsername(),
                'password'  => $revision->getPassword(),
                'notes'     => $revision->getNotes(),
                'url'       => $revision->getUrl(),
                'status'    => $revision->getStatus(),
                'hash'      => $revision->getHash(),
                'folder'    => $revision->getFolder(),
                'cseType'   => $revision->getCseType(),
                'sseType'   => $revision->getSseType(),
                'hidden'    => $revision->getHidden(),
                'trashed'   => $revision->getTrashed(),
                'favourite' => $revision->getFavourite(),
            ];

            $object['revisions'][ $revision->getUuid() ] = $current;
        }

        return $object;
    }
}