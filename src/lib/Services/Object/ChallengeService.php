<?php

namespace OCA\Passwords\Services\Object;

use Exception;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Db\ChallengeMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class ChallengeService
 *
 * @package OCA\Passwords\Services\Object
 */
class ChallengeService extends AbstractService {

    /**
     * @var ChallengeMapper|AbstractMapper
     */
    protected AbstractMapper $mapper;

    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryption;

    /**
     * @var string
     */
    protected string $class = Challenge::class;

    /**
     * ChallengeService constructor.
     *
     * @param ChallengeMapper    $mapper
     * @param UuidHelper         $uuidHelper
     * @param IEventDispatcher   $eventDispatcher
     * @param EncryptionService  $encryption
     * @param EnvironmentService $environment
     */
    public function __construct(
        ChallengeMapper $mapper,
        UuidHelper $uuidHelper,
        IEventDispatcher $eventDispatcher,
        EncryptionService $encryption,
        EnvironmentService $environment
    ) {
        parent::__construct($uuidHelper, $eventDispatcher, $environment);
        $this->mapper     = $mapper;
        $this->encryption = $encryption;
    }

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return Challenge
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function findByUuid(string $uuid, bool $decrypt = false): EntityInterface {
        /** @var Challenge $challenge */
        $challenge = parent::findByUuid($uuid);

        return $decrypt ? $this->encryption->decryptChallenge($challenge):$challenge;
    }

    /**
     * @param string $userId
     * @param bool   $decrypt
     *
     * @return Challenge[]
     * @throws Exception
     */
    public function findByUserId(string $userId, bool $decrypt = false): array {
        /** @var Challenge[] $challenges */
        $challenges = $this->mapper->findAllByUserId($userId);
        if(!$decrypt) return $challenges;

        foreach($challenges as $challenge) {
            $this->encryption->decryptChallenge($challenge);
        }

        return $challenges;
    }

    /**
     * @param string $type
     * @param string $secret
     * @param string $clientData
     * @param string $serverData
     *
     * @return Challenge
     */
    public function create(string $type, string $secret, string $clientData, string $serverData): Challenge {
        $challenge = $this->createModel($type, $secret, $clientData, $serverData);
        $this->fireEvent('instantiated', $challenge);

        return $challenge;
    }

    /**
     * @param EntityInterface|Challenge $challenge
     *
     * @return mixed
     * @throws Exception
     */
    public function save(EntityInterface $challenge): EntityInterface {
        if($challenge->_isDecrypted()) $this->encryption->encryptChallenge($challenge);

        return parent::save($challenge);
    }

    /**
     * @param string $type
     * @param string $secret
     * @param string $clientData
     * @param string $serverData
     *
     * @return Challenge
     */
    protected function createModel(string $type, string $secret, string $clientData, string $serverData): Challenge {
        $challenge = new Challenge();
        $challenge->setUserId($this->userId);
        $challenge->setUuid($this->uuidHelper->generateUuid());
        $challenge->setCreated(time());
        $challenge->setUpdated(time());
        $challenge->setDeleted(false);
        $challenge->_setDecrypted(true);

        $challenge->setType($type);
        $challenge->setClientData($clientData);
        $challenge->setServerData($serverData);
        $challenge->setSecret($secret);

        return $challenge;
    }
}