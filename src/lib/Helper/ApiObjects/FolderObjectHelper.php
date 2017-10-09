<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:45
 */

namespace OCA\Passwords\Helper\ApiObjects;

use Exception;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Services\Object\FolderService;

/**
 * Class FolderObjectHelper
 *
 * @package OCA\Passwords\Helper\ApiObjects
 */
class FolderObjectHelper {

    const LEVEL_DEFAULT = 'default';
    const LEVEL_DETAILS = 'details';

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * FolderObjectHelper constructor.
     *
     * @param FolderService $folderService
     */
    public function __construct(FolderService $folderService) {
        $this->folderService = $folderService;
    }

    /**
     * @param Folder $folder
     * @param string $level
     *
     * @return array
     * @throws Exception
     */
    public function getApiObject(Folder $folder, string $level = self::LEVEL_DEFAULT): array {
        switch ($level) {
            case self::LEVEL_DEFAULT:
                return $this->getDefaultFolderObject($folder);
                break;
            case self::LEVEL_DETAILS:
                return $this->getDetailedFolderObject($folder);
                break;
        }

        throw new Exception('Invalid information detail level');
    }

    /**
     * @param Folder $folder
     *
     * @return array
     */
    protected function getDefaultFolderObject(Folder $folder): array {

        return [
            'id'        => $folder->getUuid(),
            'owner'     => $folder->getUser(),
            'created'   => $folder->getCreated(),
            'updated'   => $folder->getUpdated(),
            'hidden'    => $folder->getHidden(),
            'trashed'   => $folder->getTrashed(),
            'name'      => $folder->getName(),
            'folders'   => [],
            'passwords' => []
        ];
    }

    /**
     * @param $folder
     *
     * @return array
     */
    protected function getDetailedFolderObject($folder): array {

        return $this->getDefaultFolderObject($folder);
    }
}