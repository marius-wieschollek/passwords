<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 14:03
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;

/**
 * Class FolderService
 *
 * @package OCA\Passwords\Services
 */
class FolderService extends AbstractModelService {
    const BASE_FOLDER_UUID = '00000000-0000-0000-0000-000000000000';

    /**
     * @var FolderMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Folder::class;

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUuid(string $uuid): AbstractModelEntity {
        if($uuid === self::BASE_FOLDER_UUID) return $this->getBaseFolder();

        return parent::findByUuid($uuid);
    }

    /**
     * @param string $uuid
     *
     * @return \OCA\Passwords\Db\AbstractEntity|Folder[]
     */
    public function findByParent(string $uuid): array {
        return $this->mapper->getByParentFolder($uuid);
    }

    /**
     * @return Folder|AbstractModelEntity
     */
    public function getBaseFolder(): Folder {

        $model = $this->createModel();
        $model->setRevision(FolderRevisionService::BASE_REVISION_UUID);
        $model->setUuid(self::BASE_FOLDER_UUID);

        return $model;
    }

    /**
     * @param AbstractModelEntity|AbstractEntity $model
     *
     * @return Folder|\OCP\AppFramework\Db\Entity
     */
    public function save(AbstractEntity $model): AbstractEntity {
        if($model->getUuid() === self::BASE_FOLDER_UUID) return $model;

        return parent::save($model);
    }

    /**
     * @param AbstractModelEntity|AbstractEntity $model
     *
     * @throws \Exception
     */
    public function delete(AbstractEntity $model): void {
        if($model->getUuid() === self::BASE_FOLDER_UUID) return;

        parent::delete($model);
    }
}