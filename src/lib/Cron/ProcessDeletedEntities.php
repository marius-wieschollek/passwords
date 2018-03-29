<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OC\BackgroundJob\TimedJob;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\AbstractService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\IUserManager;

/**
 * Class ProcessDeletedEntities
 *
 * @package OCA\Passwords\Cron
 */
class ProcessDeletedEntities extends TimedJob {

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
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

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
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var array
     */
    protected $userExists = [];

    /**
     * @var int
     */
    protected $time = 0;

    /**
     * ProcessDeletedUsers constructor.
     *
     * @param LoggingService             $logger
     * @param TagService                 $tagService
     * @param IUserManager               $userManager
     * @param ShareService               $shareService
     * @param FolderService              $folderService
     * @param ConfigurationService       $config
     * @param PasswordService            $passwordService
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
        PasswordService $passwordService,
        TagRevisionService $tagRevisionService,
        FolderRevisionService $folderRevisionService,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $passwordTagRelationService
    ) {
        // Run always
        $this->setInterval(1);

        $this->logger                     = $logger;
        $this->config                     = $config;
        $this->tagService                 = $tagService;
        $this->userManager                = $userManager;
        $this->shareService               = $shareService;
        $this->folderService              = $folderService;
        $this->passwordService            = $passwordService;
        $this->tagRevisionService         = $tagRevisionService;
        $this->folderRevisionService      = $folderRevisionService;
        $this->passwordRevisionService    = $passwordRevisionService;
        $this->passwordTagRelationService = $passwordTagRelationService;
    }

    /**
     * @param $argument
     */
    protected function run($argument): void {
        $timeout = $this->config->getAppValue('entity/purge/timeout', -1);
        if($timeout < 0) return;

        $this->time = time() - $timeout;
        $objects = $this->deleteObjects($this->tagService);
        $objects += $this->deleteObjects($this->shareService);
        $objects += $this->deleteObjects($this->folderService);
        $objects += $this->deleteObjects($this->passwordService);
        $objects += $this->deleteObjects($this->tagRevisionService);
        $objects += $this->deleteObjects($this->folderRevisionService);
        $objects += $this->deleteObjects($this->passwordRevisionService);
        $objects += $this->deleteObjects($this->passwordTagRelationService);

        $this->logger->info(['Deleted %s objects permanently', $objects]);
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
                if($this->time > $object->getUpdated() || !$this->userExists($object->getUserId())) {
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