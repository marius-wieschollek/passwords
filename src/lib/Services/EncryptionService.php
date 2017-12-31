<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 21:24
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Encryption\EncryptionInterface;
use OCA\Passwords\Encryption\ShareV1Encryption;
use OCA\Passwords\Encryption\SseV1Encryption;
use OCP\AppFramework\IAppContainer;

/**
 * Class EncryptionService
 *
 * @package OCA\Passwords\Services
 */
class EncryptionService {

    const DEFAULT_CSE_ENCRYPTION   = 'none';
    const DEFAULT_SSE_ENCRYPTION   = 'SSEv1r1';
    const DEFAULT_SHARE_ENCRYPTION = 'SSSEv1r1';
    const CSE_ENCRYPTION_NONE      = 'none';
    const SSE_ENCRYPTION_V1        = 'SSEv1r1';
    const SHARE_ENCRYPTION_V1      = 'SSSEv1r1';

    protected $encryptionMapping = [
        self::SSE_ENCRYPTION_V1   => SseV1Encryption::class,
        self::SHARE_ENCRYPTION_V1 => ShareV1Encryption::class
    ];
    /**
     * @var IAppContainer
     */
    private $container;

    /**
     * EncryptionService constructor.
     *
     * @param IAppContainer $container
     */
    public function __construct(IAppContainer $container) {
        $this->container = $container;
    }

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

        return $this->container->query($this->encryptionMapping[ $type ]);
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function encrypt(RevisionInterface $object): RevisionInterface {
        $encryption = $this->getEncryptionByType($object->getSseType());
        $object->_setDecrypted(false);

        return $encryption->encryptObject($object);
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function decrypt(RevisionInterface $object): RevisionInterface {
        $encryption = $this->getEncryptionByType($object->getSseType());
        $object->_setDecrypted(true);

        return $encryption->decryptObject($object);
    }
}