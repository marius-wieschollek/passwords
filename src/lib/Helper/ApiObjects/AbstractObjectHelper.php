<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 16:26
 */

namespace OCA\Passwords\Helper\ApiObjects;

use OCA\Passwords\Db\AbstractModelEntity;

abstract class AbstractObjectHelper {

    const LEVEL_MODEL     = 'model';
    const LEVEL_REVISIONS = 'revisions';

    abstract public function getApiObject(AbstractModelEntity $model, string $level = self::LEVEL_MODEL): array;
}