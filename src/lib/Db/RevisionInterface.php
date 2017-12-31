<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 30.12.17
 * Time: 20:28
 */

namespace OCA\Passwords\Db;

/**
 * Interface RevisionInterface
 *
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getSseKey()
 * @method void setSseKey(string $sseKey)
 * @method string getSseType()
 * @method void setSseType(string $sseType)
 * @method string getCseType()
 * @method void setCseType(string $cseType)
 * @method string getModel()
 * @method void setModel(string $model)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getClient()
 * @method void setClient(string $client)
 *
 * @package OCA\Passwords\Db
 */
interface RevisionInterface extends EntityInterface {

    /**
     * @return bool
     */
    public function _isDecrypted(): bool;

    /**
     * @param bool $decrypted
     */
    public function _setDecrypted(bool $decrypted);
}