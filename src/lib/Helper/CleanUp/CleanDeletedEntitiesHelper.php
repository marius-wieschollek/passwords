<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\CleanUp;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\AbstractService;
use OCA\Passwords\Services\Object\ChallengeService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\IUserManager;

/**
 * Class CleanDeletedEntitiesHelper
 *
 * @package OCA\Passwords\Helper\CleanUp
 */
class CleanDeletedEntitiesHelper {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var KeychainService
     */
    protected $keychainService;

    /**
     * @var ChallengeService
     */
    protected $challengeService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * @var FolderRevisionService
     */
    protected $folderRevisionService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $passwordTagRelationService;

    /**
     * @var array
     */
    protected $userExists = [];

    /**
     * @var int
     */
    protected $time = 0;

    /**
     * CleanDeletedEntitiesHelper constructor.
     *
     * @param LoggingService             $logger
     * @param TagService                 $tagService
     * @param IUserManager               $userManager
     * @param ShareService               $shareService
     * @param FolderService              $folderService
     * @param ConfigurationService       $config
     * @param EnvironmentService         $environment
     * @param PasswordService            $passwordService
     * @param KeychainService            $keychainService
     * @param ChallengeService           $challengeService
     * @param TagRevisionService         $tagRevisionService
     * @param FolderRevisionService      $folderRevisionService
     * @param PasswordRevisionService    $passwordRevisionService
     * @param PasswordTagRelationService $passwordTagRelationService
     */
    public function __construct(
        LoggingService $logger,
        TagService $tagService,
        IUserManager $userManager,
        ShareService $shareService,
        FolderService $folderService,
        ConfigurationService $config,
        EnvironmentService $environment,
        PasswordService $passwordService,
        KeychainService $keychainService,
        ChallengeService $challengeService,
        TagRevisionService $tagRevisionService,
        FolderRevisionService $folderRevisionService,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $passwordTagRelationService
    ) {
        $this->logger                     = $logger;
        $this->config                     = $config;
        $this->tagService                 = $tagService;
        $this->userManager                = $userManager;
        $this->shareService               = $shareService;
        $this->folderService              = $folderService;
        $this->passwordService            = $passwordService;
        $this->keychainService            = $keychainService;
        $this->challengeService           = $challengeService;
        $this->tagRevisionService         = $tagRevisionService;
        $this->folderRevisionService      = $folderRevisionService;
        $this->passwordRevisionService    = $passwordRevisionService;
        $this->passwordTagRelationService = $passwordTagRelationService;
    }

    /**
     *
     */
    public function run(): void {
        $timeout = $this->config->getAppValue('entity/purge/timeout', -1);
        if($timeout >= 0) $this->time = time() - $timeout;

        $objects = $this->deleteObjects($this->tagService);
        $objects += $this->deleteObjects($this->shareService);
        $objects += $this->deleteObjects($this->folderService);
        $objects += $this->deleteObjects($this->passwordService);
        $objects += $this->deleteObjects($this->keychainService);
        $objects += $this->deleteObjects($this->challengeService);
        $objects += $this->deleteObjects($this->tagRevisionService);
        $objects += $this->deleteObjects($this->folderRevisionService);
        $objects += $this->deleteObjects($this->passwordRevisionService);
        $objects += $this->deleteObjects($this->passwordTagRelationService);

        $this->logger->debugOrInfo(['Deleted %s object(s) permanently', $objects], $objects);
    }

    /**
     * @param $service
     *
     * @return int
     */
    protected function deleteObjects(AbstractService $service): int {
        try {
            /** @var EntityInterface[] $objects */
            $objects = $service->findDeleted();

            $counter = 0;
            foreach($objects as $object) {
                if(($this->time > 0 && $this->time > $object->getUpdated()) || !$this->userExists($object->getUserId())) {
                    $counter++;
                    $service->destroy($object);
                }
            }

            return $counter;
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return 0;
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    protected function userExists(string $userId): bool {
        if(!isset($this->userExists[ $userId ])) {
            $this->userExists[ $userId ] = $this->userManager->userExists($userId);
        }

        return $this->userExists[ $userId ];
    }
}