<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Db\KeychainMapper;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class KeychainService
 *
 * @package OCA\Passwords\Services\Object
 */
class KeychainService extends AbstractService {

    /**
     * @var KeychainMapper
     */
    protected $mapper;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;

    /**
     * @var string
     */
    protected $class = Keychain::class;

    /**
     * KeychainService constructor.
     *
     * @param KeychainMapper     $mapper
     * @param UuidHelper         $uuidHelper
     * @param HookManager        $hookManager
     * @param EnvironmentService $environment
     * @param EncryptionService  $encryptionService
     */
    public function __construct(
        KeychainMapper $mapper,
        UuidHelper $uuidHelper,
        HookManager $hookManager,
        EnvironmentService $environment,
        EncryptionService $encryptionService
    ) {
        parent::__construct($uuidHelper, $hookManager, $environment);
        $this->mapper            = $mapper;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param bool $decrypt
     *
     * @return Keychain[]
     * @throws \Exception
     */
    public function findAll(bool $decrypt = false): array {
        /** @var Keychain[] $keychains */
        $keychains = $this->mapper->findAll();

        return $decrypt ? $this->decryptArray($keychains):$keychains;
    }

    /**
     * @param string $userId
     * @param bool   $decrypt
     *
     * @return Keychain[]
     * @throws \Exception
     */
    public function findByUserId(string $userId, bool $decrypt = false): array {
        /** @var Keychain[] $keychains */
        $keychains = $this->mapper->findAllByUserId($userId);

        return $decrypt ? $this->decryptArray($keychains):$keychains;
    }

    /**
     * @param string $scope
     * @param bool   $decrypt
     *
     * @return Keychain[]
     * @throws \Exception
     */
    public function findByScope(string $scope, bool $decrypt = false): array {
        /** @var Keychain[] $keychains */
        $keychains = $this->mapper->findAllByScope($scope);

        return $decrypt ? $this->decryptArray($keychains):$keychains;
    }

    /**
     * @param string $type
     * @param bool   $decrypt
     *
     * @return Keychain
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function findByType(string $type, bool $decrypt = false): Keychain {
        /** @var Keychain $keychain */
        $keychain = $this->mapper->findOneByType($type);

        return $decrypt ? $this->encryptionService->decryptKeychain($keychain):$keychain;
    }

    /**
     * @return Keychain[]
     * @throws \Exception
     */
    public function getClientKeychainArray(): array {
        $keychains = $this->findByScope(Keychain::SCOPE_CLIENT, true);

        $list = [];
        foreach($keychains as $keychain) {
            $list[ $keychain->getType() ] = $keychain->getData();
        }

        return $list;
    }

    /**
     * @param string $type
     * @param string $data
     * @param string $scope
     *
     * @return Keychain
     */
    public function create(string $type, string $data, string $scope): Keychain {
        $keychain = $this->createModel($type, $data, $scope);

        $this->hookManager->emit($this->class, 'postCreate', [$keychain]);

        return $keychain;
    }

    /**
     * @param EntityInterface|Keychain $challenge
     *
     * @return mixed
     * @throws \Exception
     */
    public function save(EntityInterface $challenge): EntityInterface {
        $this->hookManager->emit($this->class, 'preSave', [$challenge]);

        if($challenge->_isDecrypted()) $this->encryptionService->encryptKeychain($challenge);

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
     * @param string $data
     * @param string $scope
     *
     * @return Keychain
     */
    protected function createModel(string $type, string $data, string $scope): Keychain {
        $keychain = new Keychain();
        $keychain->setUserId($this->userId);
        $keychain->setUuid($this->uuidHelper->generateUuid());
        $keychain->setCreated(time());
        $keychain->setUpdated(time());
        $keychain->setDeleted(false);
        $keychain->_setDecrypted(true);

        $keychain->setType($type);
        $keychain->setData($data);
        $keychain->setScope($scope);

        return $keychain;
    }

    /**
     * @param array $keychains
     *
     * @return array
     * @throws \Exception
     */
    protected function decryptArray(array $keychains): array {
        foreach($keychains as $keychain) {
            $this->encryptionService->decryptKeychain($keychain);
        }

        return $keychains;
    }
}