<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services\Object;

use Exception;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\AbstractRevisionMapper;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\ValidationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Class AbstractRevisionService
 *
 * @package OCA\Passwords\Services\Object
 */
abstract class AbstractRevisionService extends AbstractService {

    /**
     * @var ValidationService
     */
    protected ValidationService $validation;

    /**
     * @var EncryptionService
     */
    protected EncryptionService $encryption;

    /**
     * @var AbstractRevisionMapper|AbstractMapper
     */
    protected AbstractMapper $mapper;

    /**
     * AbstractRevisionService constructor.
     *
     * @param UuidHelper             $uuidHelper
     * @param IEventDispatcher       $eventDispatcher
     * @param EnvironmentService     $environment
     * @param AbstractRevisionMapper $revisionMapper
     * @param ValidationService      $validationService
     * @param EncryptionService      $encryption
     */
    public function __construct(
        UuidHelper             $uuidHelper,
        IEventDispatcher       $eventDispatcher,
        EnvironmentService     $environment,
        AbstractRevisionMapper $revisionMapper,
        ValidationService      $validationService,
        EncryptionService      $encryption
    ) {
        $this->mapper     = $revisionMapper;
        $this->validation = $validationService;
        $this->encryption = $encryption;

        parent::__construct($uuidHelper, $eventDispatcher, $environment);
    }

    /**
     * Cunt all revisions
     *
     * @return int
     */
    public function count() {
        return $this->mapper->count();
    }

    /**
     * @param bool $decrypt
     *
     * @return RevisionInterface[]
     * @throws Exception
     */
    public function findAll(bool $decrypt = false, ?string $userId = null): array {
        /** @var RevisionInterface[] $revisions */
        if($userId === null) {
            $revisions = $this->mapper->findAll();
        } else {
            $revisions = $this->mapper->findAllByUserId($userId);
        }

        if(!$decrypt) return $revisions;

        foreach($revisions as $revision) {
            $this->encryption->decrypt($revision);
        }

        return $revisions;
    }

    /**
     * @param bool $decrypt
     *
     * @return RevisionInterface[]
     * @throws Exception
     */
    public function findAllHidden(bool $decrypt = false): array {
        /** @var RevisionInterface[] $revisions */
        $revisions = $this->mapper->findAllHidden();
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
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function save(EntityInterface $revision): EntityInterface {
        if(get_class($revision) !== $this->class) throw new Exception('Invalid revision class given');

        if($revision->_isDecrypted()) $this->encryption->encrypt($revision);

        return $this->saveModel($revision);
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
            } catch(Exception $e) {
            }
        }

        return EncryptionService::DEFAULT_SSE_ENCRYPTION;
    }
}