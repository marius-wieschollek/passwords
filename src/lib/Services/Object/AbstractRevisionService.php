<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 24.12.17
 * Time: 12:35
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;

/**
 * Class AbstractRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractRevisionService extends AbstractService {

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var EncryptionService
     */
    protected $encryptionService;

    /**
     * @var AbstractMapper
     */
    protected $mapper;

    /**
     * PasswordService constructor.
     *
     * @param string            $userId
     * @param HookManager       $hookManager
     * @param ValidationService $validationService
     * @param EncryptionService $encryptionService
     * @param AbstractMapper    $revisionMapper
     */
    public function __construct(
        ?string $userId,
        HookManager $hookManager,
        AbstractMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryptionService
    ) {
        $this->mapper            = $revisionMapper;
        $this->validationService = $validationService;
        $this->encryptionService = $encryptionService;

        parent::__construct($userId, $hookManager);
    }

    /**
     * @param string $uuid
     * @param bool   $decrypt
     *
     * @return AbstractRevisionEntity
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function findByUuid(string $uuid, bool $decrypt = true): AbstractRevisionEntity {
        /** @var AbstractRevisionEntity $revision */
        $revision = $this->mapper->findByUuid($uuid);

        return $decrypt ? $this->encryptionService->decrypt($revision):$revision;
    }

    /**
     * @param string $modelUuid
     * @param bool   $decrypt
     *
     * @return AbstractRevisionEntity[]
     *
     * @throws \Exception
     */
    public function findByModel(string $modelUuid, bool $decrypt = true): array {
        /** @var AbstractRevisionEntity[] $revisions */
        $revisions = $this->mapper->findAllMatching(['model', $modelUuid]);

        if(!$decrypt) return $revisions;

        foreach ($revisions as $revision) {
            $this->encryptionService->decrypt($revision);
        }

        return $revisions;
    }

    /**
     * @param AbstractEntity|AbstractRevisionEntity $revision
     *
     * @return AbstractRevisionEntity|\OCP\AppFramework\Db\Entity
     * @throws \Exception
     */
    public function save(AbstractEntity $revision): AbstractEntity {
        if(get_class($revision) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preSave', [$revision]);

        if($revision->_isDecrypted()) $revision = $this->encryptionService->encrypt($revision);

        if(empty($revision->getId())) {
            return $this->mapper->insert($revision);
        } else {
            $revision->setUpdated(time());

            return $this->mapper->update($revision);
        }
    }

    /**
     * @param AbstractEntity|AbstractRevisionEntity $original
     * @param array                                 $overwrites
     *
     * @return AbstractEntity|AbstractRevisionEntity
     */
    protected function cloneModel(AbstractEntity $original, array $overwrites = []): AbstractEntity {

        /** @var AbstractRevisionEntity $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->_setDecrypted($original->_isDecrypted());
        $clone->setUuid($this->generateUuidV4());

        return $clone;
    }
}