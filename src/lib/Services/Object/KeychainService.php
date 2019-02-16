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
     * @param HookManager        $hookManager
     * @param KeychainMapper     $mapper
     * @param EnvironmentService $environment
     * @param EncryptionService  $encryptionService
     */
    public function __construct(
        HookManager $hookManager,
        KeychainMapper $mapper,
        EnvironmentService $environment,
        EncryptionService $encryptionService
    ) {
        parent::__construct($hookManager, $environment);
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
     * @return Keychain|null
     * @throws \Exception
     */
    public function findByType(string $type, bool $decrypt = false): ?Keychain {
        /** @var Keychain $keychain */
        try {
            $keychain = $this->mapper->findOneByType($type);
        } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
            return null;
        }

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
     * @param EntityInterface|Keychain $keychain
     *
     * @return mixed
     * @throws \Exception
     */
    public function save(EntityInterface $keychain): EntityInterface {
        $this->hookManager->emit($this->class, 'preSave', [$keychain]);

        if($keychain->_isDecrypted()) $this->encryptionService->encryptKeychain($keychain);

        if(empty($keychain->getId())) {
            $saved = $this->mapper->insert($keychain);
        } else {
            $keychain->setUpdated(time());
            $saved = $this->mapper->update($keychain);
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
        $keychain->setUuid($this->generateUuidV4());
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