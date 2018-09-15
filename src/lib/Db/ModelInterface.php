<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Db;

/**
 * Interface ModelInterface
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getRevision()
 * @method void setRevision(string $revision)
 *
 * @package OCA\Passwords\Db
 */
interface ModelInterface extends EntityInterface {
}