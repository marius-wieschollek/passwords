<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:24
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Encryption\EncryptionInterface;
use OCA\Passwords\Encryption\SseV1Encryption;

/**
 * Class EncryptionService
 *
 * @package OCA\Passwords\Services
 */
class EncryptionService {

    const DEFAULT_CSE_ENCRYPTION = 'none';
    const DEFAULT_SSE_ENCRYPTION = 'SSEv1r1';
    const CSE_ENCRYPTION_NONE    = 'none';
    const SSE_ENCRYPTION_V1      = 'SSEv1r1';

    protected $encryptionMapping
        = [
            self::SSE_ENCRYPTION_V1 => SseV1Encryption::class
        ];

    /**
     * @param string $type
     *
     * @return EncryptionInterface
     * @throws \Exception
     */
    public function getEncryptionByType(string $type): EncryptionInterface {

        if(!isset($this->encryptionMapping[ $type ])) {
            throw new \Exception("Encryption type {$type} does not exsist");
        }

        return new $this->encryptionMapping[$type];
    }

    /**
     * @param AbstractRevisionEntity $object
     *
     * @return AbstractRevisionEntity
     * @throws \Exception
     */
    public function encrypt(AbstractRevisionEntity $object): AbstractRevisionEntity {
        $encryption = $this->getEncryptionByType($object->getSseType());
        $object->_setDecrypted(false);

        return $encryption->encryptObject($object);
    }

    /**
     * @param AbstractRevisionEntity $object
     *
     * @return AbstractRevisionEntity
     * @throws \Exception
     */
    public function decrypt(AbstractRevisionEntity $object): AbstractRevisionEntity {
        $encryption = $this->getEncryptionByType($object->getSseType());
        $object->_setDecrypted(true);

        return $encryption->decryptObject($object);
    }
}