<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:18
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;

/**
 * Class PasswordService
 *
 * @package OCA\Passwords\Services
 */
class PasswordService extends AbstractModelService {

    /**
     * @var PasswordMapper
     */
    protected $mapper;

    /**
     * @var string
     */
    protected $class = Password::class;

    /**
     * @param string $uuid
     *
     * @return Password[]
     */
    public function findByFolder(string $uuid): array {
        return $this->mapper->getByFolder($uuid);
    }

    /**
     * @param string $tagUuid
     * @param bool   $includeHidden
     *
     * @return Password[]
     */
    public function findByTag(string $tagUuid, bool $includeHidden = false): array {
        return $this->mapper->getByTag($tagUuid, $includeHidden);
    }

    /**
     * @return Password
     */
    protected function createModel(): ModelInterface {
        /** @var Password $model */
        $model = parent::createModel();
        $model->setEditable(true);

        return $model;
    }
}