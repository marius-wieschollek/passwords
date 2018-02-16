<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services\Object;

use OCA\Passwords\Db\AbstractRevisionMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\ValidationService;
use OCP\AppFramework\Db\Entity;

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
     * @var AbstractRevisionMapper
     */
    protected $mapper;

    /**
     * PasswordService constructor.
     *
     * @param string                 $userId
     * @param HookManager            $hookManager
     * @param AbstractRevisionMapper $revisionMapper
     * @param ValidationService      $validationService
     * @param EncryptionService      $encryptionService
     */
    public function __construct(
        ?string $userId,
        HookManager $hookManager,
        AbstractRevisionMapper $revisionMapper,
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
     * @return RevisionInterface
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     * @throws \Exception
     */
    public function findByUuid(string $uuid, bool $decrypt = false): RevisionInterface {
        /** @var RevisionInterface $revision */
        $revision = $this->mapper->findByUuid($uuid);

        return $decrypt ? $this->encryptionService->decrypt($revision):$revision;
    }

    /**
     * @param string $modelUuid
     * @param bool   $decrypt
     *
     * @return RevisionInterface[]
     *
     * @throws \Exception
     */
    public function findByModel(string $modelUuid, bool $decrypt = false): array {
        /** @var RevisionInterface[] $revisions */
        $revisions = $this->mapper->findAllMatching(['model', $modelUuid]);

        if(!$decrypt) return $revisions;

        foreach($revisions as $revision) {
            $this->encryptionService->decrypt($revision);
        }

        return $revisions;
    }

    /**
     * @param string $modelUuid
     * @param bool   $decrypt
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function findCurrentRevisionByModel(string $modelUuid, bool $decrypt = false): RevisionInterface {
        /** @var RevisionInterface $revision */
        $revision = $this->mapper->findCurrentRevisionByModel($modelUuid);

        return $decrypt ? $this->encryptionService->decrypt($revision):$revision;
    }

    /**
     * @param EntityInterface|RevisionInterface|Entity $revision
     *
     * @return RevisionInterface|Entity
     * @throws \Exception
     */
    public function save(EntityInterface $revision): EntityInterface {
        if(get_class($revision) !== $this->class) throw new \Exception('Invalid revision class given');
        $this->hookManager->emit($this->class, 'preSave', [$revision]);

        if($revision->_isDecrypted()) $revision = $this->encryptionService->encrypt($revision);

        if(empty($revision->getId())) {
            $saved = $this->mapper->insert($revision);
        } else {
            $revision->setUpdated(time());

            $saved = $this->mapper->update($revision);
        }
        $this->hookManager->emit($this->class, 'postSave', [$saved]);

        return $saved;
    }

    /**
     * @param RevisionInterface|EntityInterface $original
     * @param array                             $overwrites
     *
     * @return RevisionInterface
     */
    protected function cloneModel(EntityInterface $original, array $overwrites = []): EntityInterface {

        /** @var RevisionInterface $clone */
        $clone = parent::cloneModel($original, $overwrites);
        $clone->_setDecrypted($original->_isDecrypted());
        $clone->setUuid($this->generateUuidV4());

        return $clone;
    }
}