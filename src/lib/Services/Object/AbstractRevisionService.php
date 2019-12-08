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
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Hooks\Manager\HookManager;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
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
    protected $validation;

    /**
     * @var EncryptionService
     */
    protected $encryption;

    /**
     * @var AbstractRevisionMapper
     */
    protected $mapper;

    /**
     * AbstractRevisionService constructor.
     *
     * @param UuidHelper             $uuidHelper
     * @param HookManager            $hookManager
     * @param EnvironmentService     $environment
     * @param AbstractRevisionMapper $revisionMapper
     * @param ValidationService      $validationService
     * @param EncryptionService      $encryption
     */
    public function __construct(
        UuidHelper $uuidHelper,
        HookManager $hookManager,
        EnvironmentService $environment,
        AbstractRevisionMapper $revisionMapper,
        ValidationService $validationService,
        EncryptionService $encryption
    ) {
        $this->mapper     = $revisionMapper;
        $this->validation = $validationService;
        $this->encryption = $encryption;

        parent::__construct($uuidHelper, $hookManager, $environment);
    }

    /**
     * @param bool $decrypt
     *
     * @return RevisionInterface[]
     * @throws \Exception
     */
    public function findAll($decrypt = false): array {
        /** @var RevisionInterface[] $revisions */
        $revisions = $this->mapper->findAll();
        if(!$decrypt) return $revisions;

        foreach($revisions as $revision) {
            $this->encryption->decrypt($revision);
        }

        return $revisions;
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

        return $decrypt ? $this->encryption->decrypt($revision):$revision;
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
        $revisions = $this->mapper->findAllByModel($modelUuid);

        if(!$decrypt) return $revisions;

        foreach($revisions as $revision) {
            $this->encryption->decrypt($revision);
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

        return $decrypt ? $this->encryption->decrypt($revision):$revision;
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

        if($revision->_isDecrypted()) $this->encryption->encrypt($revision);

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
        $clone->setUuid($this->uuidHelper->generateUuid());
        $clone->setClient($this->environment->getClient());

        return $clone;
    }

    /**
     * @param string $cseType
     *
     * @return string
     */
    protected function getSseEncryption(string $cseType): string {
        if($this->userId) {
            try {
                return $this->encryption->getDefaultEncryption($cseType, $this->userId);
            } catch(\Exception $e) {
            }
        }

        return EncryptionService::DEFAULT_SSE_ENCRYPTION;
    }
}