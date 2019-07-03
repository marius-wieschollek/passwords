<?php

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Db\ChallengeMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;

/**
 * Class ChallengeService
 *
 * @package OCA\Passwords\Services\Object
 */
class ChallengeService extends AbstractService {

    /**
     * @var ChallengeMapper
     */
    protected $mapper;

    /**
     * @var EncryptionService
     */
    protected $encryption;

    /**
     * @var string
     */
    protected $class = Challenge::class;

    /**
     * ChallengeService constructor.
     *
     * @param ChallengeMapper    $mapper
     * @param UuidHelper         $uuidHelper
     * @param HookManager        $hookManager
     * @param EnvironmentService $environment
     * @param EncryptionService  $encryption
     */
    public function __construct(
        ChallengeMapper $mapper,
        UuidHelper $uuidHelper,
        HookManager $hookManager,
        EncryptionService $encryption,
        EnvironmentService $environment
    ) {
        parent::__construct($uuidHelper, $hookManager, $environment);
        $this->mapper     = $mapper;
        $this->encryption = $encryption;
    }

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return Challenge
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
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
     * @throws \Exception
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

        $this->hookManager->emit($this->class, 'postCreate', [$challenge]);

        return $challenge;
    }

    /**
     * @param EntityInterface|Challenge $challenge
     *
     * @return mixed
     * @throws \Exception
     */
    public function save(EntityInterface $challenge): EntityInterface {
        $this->hookManager->emit($this->class, 'preSave', [$challenge]);

        if($challenge->_isDecrypted()) $this->encryption->encryptChallenge($challenge);

        if(empty($challenge->getId())) {
            $saved = $this->mapper->insert($challenge);
        } else {
            $challenge->setUpdated(time());
            $saved = $this->mapper->update($challenge);
        }
        $this->hookManager->emit($this->class, 'postSave', [$saved]);

        return $saved;
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