<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 23:59
 */

namespace OCA\Passwords\Encryption;

use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Db\ShareRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\Object\ShareRevisionService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

/**
 * Class ShareV1Encryption
 *
 * @package OCA\Passwords\Encryption
 */
class ShareV1Encryption extends SseV1Encryption {

    /**
     * @var array
     */
    protected $share
        = [
            'url',
            'label',
            'notes',
            'password',
            'username'
        ];

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var ShareRevisionService
     */
    protected $revisionService;

    /**
     * ShareV1Encryption constructor.
     *
     * @param null|string          $userId
     * @param ICrypto              $crypto
     * @param ISecureRandom        $secureRandom
     * @param ConfigurationService $configurationService
     * @param ShareService         $shareService
     * @param ShareRevisionService $revisionService
     */
    public function __construct(
        ?string $userId,
        ICrypto $crypto,
        ISecureRandom $secureRandom,
        ConfigurationService $configurationService,
        ShareService $shareService,
        ShareRevisionService $revisionService
    ) {
        parent::__construct($userId, $crypto, $secureRandom, $configurationService);
        $this->shareService    = $shareService;
        $this->revisionService = $revisionService;
    }

    /**
     * @param RevisionInterface|ShareRevision $object
     *
     * @return RevisionInterface|ShareRevision
     * @throws \Exception
     * @throws \OCP\PreConditionNotMetException
     */
    public function encryptObject(RevisionInterface $object): RevisionInterface {
        if(get_class($object) === ShareRevision::class) {
            if(!$object->isEditable() && $object->getUserId() !== $this->userId) {
                throw new ApiException('Share is not editable', 403);
            }

            return $this->encryptShare($object);
        }

        if($object->getUserId() === $this->userId) {
            return parent::encryptObject($object);
        } else {
            $fields = $this->getFieldsToProcess($object);
            foreach ($fields as $field) {
                $object->setProperty($field, null);
            }
            $object->setSseKey('');

            return $object;
        }
    }

    /**
     * @param RevisionInterface|ShareRevision $object
     *
     * @return RevisionInterface|ShareRevision
     * @throws \Exception
     */
    public function decryptObject(RevisionInterface $object): RevisionInterface {
        if(get_class($object) === ShareRevision::class) {
            return $this->decryptShare($object);
        }

        if($object->getUserId() === $this->userId) {
            // @TODO check if new revision available
            return parent::decryptObject($object);
        } else {
            $share    = $this->shareService->findByReceiverAndPassword($this->userId, $object->getUuid());
            $revision = $this->revisionService->findByUuid($share->getUuid());

            $fields = $this->getFieldsToProcess($object);
            foreach ($fields as $field) {
                $object->setProperty(
                    $field,
                    $revision->getProperty($field)
                );
            }

            return $object;
        }
    }

    /**
     * @param ShareRevision $object
     *
     * @return ShareRevision
     */
    protected function encryptShare(ShareRevision $object): ShareRevision {
        $sseKey        = $this->getSecureRandom();
        $encryptionKey = $this->getShareEncryptionKey($sseKey);

        foreach ($this->share as $field) {
            $value          = $object->getProperty($field);
            $encryptedValue = $this->crypto->encrypt($value, $encryptionKey);
            $object->setProperty($field, base64_encode($encryptedValue));
        }

        $object->setSseKey(base64_encode($sseKey));

        return $object;
    }

    /**
     * @param ShareRevision $object
     *
     * @return ShareRevision
     * @throws \Exception
     */
    public function decryptShare(ShareRevision $object): ShareRevision {
        $sseKey        = base64_decode($object->getSseKey());
        $encryptionKey = $this->getShareEncryptionKey($sseKey);

        foreach ($this->share as $field) {
            $value          = base64_decode($object->getProperty($field));
            $decryptedValue = $this->crypto->decrypt($value, $encryptionKey);
            $object->setProperty($field, $decryptedValue);
        }

        return $object;
    }

    /**
     * @param string $shareKey
     *
     * @return string
     */
    protected function getShareEncryptionKey(string $shareKey): string {
        return base64_encode($this->getServerKey().$shareKey);
    }
}