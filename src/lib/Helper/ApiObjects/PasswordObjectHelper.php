<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 00:19
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Services\Object\RevisionService;

/**
 * Class PasswordObjectHelper
 *
 * @package OCA\Passwords\Helper
 */
class PasswordObjectHelper {

    const LEVEL_DEFAULT = 'default';
    const LEVEL_DETAILS = 'details';

    /**
     * @var RevisionService
     */
    protected $revisionService;

    /**
     * PasswordApiController constructor.
     *
     * @param RevisionService $revisionService
     */
    public function __construct(RevisionService $revisionService) {
        $this->revisionService = $revisionService;
    }

    /**
     * @param Password $password
     * @param string   $level
     *
     * @return array
     * @throws Exception
     */
    public function getApiObject(Password $password, string $level = self::LEVEL_DEFAULT) {
        switch ($level) {
            case self::LEVEL_DEFAULT:
                return $this->getDefaultPasswordObject($password);
                break;
            case self::LEVEL_DETAILS:
                return $this->getDetailedPasswordObject($password);
                break;
        }

        throw new Exception('Invalid information detail level');
    }

    /**
     * @param Password $password
     *
     * @return array
     */
    protected function getDefaultPasswordObject(Password $password): array {
        $revision = $this->revisionService->getCurrentRevision($password);

        return [
            'id'        => $password->getUuid(),
            'owner'     => $password->getUser(),
            'created'   => $password->getCreated(),
            'updated'   => $password->getUpdated(),
            'revision'  => $revision->getUuid(),
            'title'     => $revision->getTitle(),
            'login'     => $revision->getLogin(),
            'password'  => $revision->getPassword(),
            'notes'     => $revision->getNotes(),
            'url'       => $revision->getUrl(),
            'status'    => $revision->getStatus(),
            'hash'      => $revision->getHash(),
            'cseType'   => $revision->getCseType(),
            'sseType'   => $revision->getSseType(),
            'hidden'    => $revision->getHidden(),
            'trashed'   => $revision->getTrashed(),
            'favourite' => $revision->getFavourite(),
            'tags'      => [],
            'folders'   => [],
        ];
    }

    /**
     * @param Password $password
     *
     * @return array
     */
    protected function getDetailedPasswordObject(Password $password): array {
        $object  = $this->getDefaultPasswordObject($password);
        $revisions = $this->revisionService->getRevisionsByPassword($password->getUuid());

        $object['revisions'] = [];
        foreach ($revisions as $revision) {
            [
                $object['revisions'][] = [
                    'id'        => $revision->getUuid(),
                    'owner'     => $revision->getUser(),
                    'created'   => $revision->getCreated(),
                    'updated'   => $revision->getUpdated(),
                    'title'     => $revision->getTitle(),
                    'login'     => $revision->getLogin(),
                    'password'  => $revision->getPassword(),
                    'notes'     => $revision->getNotes(),
                    'url'       => $revision->getUrl(),
                    'status'    => $revision->getStatus(),
                    'hash'      => $revision->getHash(),
                    'cseType'   => $revision->getCseType(),
                    'sseType'   => $revision->getSseType(),
                    'hidden'    => $revision->getHidden(),
                    'trashed'   => $revision->getTrashed(),
                    'favourite' => $revision->getFavourite(),
                ]
            ];
        }

        return $object;
    }
}