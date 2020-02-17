<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\MailService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class SynchronizeShares
 *
 * @package OCA\Passwords\Cron
 */
class SynchronizeShares extends AbstractTimedJob {

    const EXECUTION_TIMESTAMP = 'cron/sharing/time';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * @var array
     */
    protected $notifications = ['created' => [], 'deleted' => [], 'loop' => []];

    /**
     * SynchronizeShares constructor.
     *
     * @param LoggingService          $logger
     * @param MailService             $mailService
     * @param ShareService            $shareService
     * @param ConfigurationService    $config
     * @param EnvironmentService      $environment
     * @param PasswordService         $passwordService
     * @param NotificationService     $notificationService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        LoggingService $logger,
        MailService $mailService,
        ShareService $shareService,
        ConfigurationService $config,
        EnvironmentService $environment,
        PasswordService $passwordService,
        NotificationService $notificationService,
        PasswordRevisionService $passwordRevisionService
    ) {
        $this->config                  = $config;
        $this->mailService             = $mailService;
        $this->shareService            = $shareService;
        $this->passwordService         = $passwordService;
        $this->notificationService     = $notificationService;
        $this->passwordRevisionService = $passwordRevisionService;
        parent::__construct($logger, $environment);
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument): void {
        if(!$this->canExecute()) return;
        $this->config->setAppValue(self::EXECUTION_TIMESTAMP, time());

        $this->deleteOrphanedTargetPasswords();
        $this->deleteExpiredShares();
        $this->createNewShares();
        $this->removeSharedAttribute();
        $this->updatePasswords();
        $this->notifyUsers();

        $this->config->deleteAppValue(self::EXECUTION_TIMESTAMP);
    }

    /**
     * @return bool
     */
    public function runManually(): bool {
        try {
            if($this->canExecute()) {
                $this->runJob($this->getArgument());

                return true;
            }
        } catch(\Exception $e) {
            $this->logger->logException($e);
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    protected function deleteOrphanedTargetPasswords(): void {
        $total = 0;
        do {
            $passwords = $this->passwordService->findOrphanedTargetPasswords();
            $count     = count($passwords);
            $total     += $count;

            foreach($passwords as $password) {
                $shares = $this->shareService->findBySourcePassword($password->getUuid());
                foreach($shares as $share) {
                    $this->shareService->delete($share);
                }
                $this->passwordService->delete($password);
            }
        } while($count !== 0);

        $this->logger->debugOrInfo(['Deleted %s orphaned password(s)', $total], $total);
    }

    /**
     * @throws \Exception
     */
    protected function deleteExpiredShares(): void {
        $total = 0;
        do {
            $shares = $this->shareService->findExpired();
            $count  = count($shares);
            $total  += $count;

            foreach($shares as $share) {
                try {
                    $password = $this->passwordService->findByUuid($share->getTargetPassword());
                    $this->passwordService->delete($password);
                } catch(DoesNotExistException $e) {
                }
                $this->shareService->delete($share);
            }
        } while($count !== 0);

        $this->logger->debugOrInfo(['Deleted %s expired share(s)', $total], $total);
    }

    /**
     * @throws \Exception
     */
    protected function createNewShares(): void {
        $shares = $this->shareService->findNew();

        foreach($shares as $share) {
            if($this->shareLineageHasLoop($share)) continue;
            $receiverId = $share->getReceiver();
            $userId     = $share->getUserId();

            /** @var Password $model */
            $model = $this->passwordService->create();
            $model->setUserId($receiverId);
            $model->setShareId($share->getUuid());
            $model->setEditable($share->getEditable());

            /** @var PasswordRevision $sourceRevision */
            $sourceRevision = $this->passwordRevisionService->findCurrentRevisionByModel($share->getSourcePassword(), true);
            $revision       = $this->passwordRevisionService->create(
                $model->getUuid(),
                $sourceRevision->getPassword(),
                $sourceRevision->getUsername(),
                '',
                $sourceRevision->getCseType(),
                $sourceRevision->getHash(),
                $sourceRevision->getLabel(),
                $sourceRevision->getUrl(),
                $sourceRevision->getNotes(),
                $sourceRevision->getCustomFields(),
                FolderService::BASE_FOLDER_UUID,
                time(),
                false,
                false,
                false
            );
            $revision->setUserId($receiverId);

            $this->passwordRevisionService->save($revision);
            $this->passwordService->setRevision($model, $revision);

            $share->setTargetPassword($model->getUuid());
            $share->setSourceUpdated(false);
            $this->shareService->save($share);

            if(!isset($this->notifications['created'][ $receiverId ])) $this->notifications['created'][ $receiverId ] = [];
            if(!isset($this->notifications['created'][ $receiverId ][ $userId ])) $this->notifications['created'][ $receiverId ][ $userId ] = 0;
            $this->notifications['created'][ $receiverId ][ $userId ]++;
        }

        $total = count($shares);
        $this->logger->debugOrInfo(['Created %s new share(s)', $total], $total);
    }

    /**
     * @param Share $share
     *
     * @return bool
     * @throws MultipleObjectsReturnedException
     * @throws \Exception
     */
    protected function shareLineageHasLoop(Share $share): bool {
        $sourceUuid = $share->getSourcePassword();
        while(1) {
            try {
                /** @var Password $password */
                $password = $this->passwordService->findByUuid($sourceUuid);
            } catch(DoesNotExistException $e) {
                return false;
            }
            if($password->getUserId() === $share->getReceiver()) {
                $this->shareService->delete($share);

                $userId = $share->getUserId();
                if(!isset($this->notifications['loop'][ $userId ])) {
                    $this->notifications['loop'][ $userId ] = 0;
                }
                $this->notifications['loop'][ $userId ]++;

                return true;
            }
            if($password->getShareId() === null) return false;

            try {
                $parentShare = $this->shareService->findByUuid($password->getShareId());
                $sourceUuid  = $parentShare->getSourcePassword();
            } catch(DoesNotExistException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    protected function removeSharedAttribute(): void {
        $passwords = $this->passwordService->findShared();
        $total     = 0;

        foreach($passwords as $password) {
            $shares = $this->shareService->findBySourcePassword($password->getUuid());

            if(empty($shares)) {
                $password->setHasShares(false);
                $this->passwordService->save($password);
                $total++;
            }
        }

        $this->logger->debugOrInfo(['Removed shared attribute from %s password(s)', $total], $total);
    }

    /**
     * @throws DoesNotExistException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updatePasswords(): void {
        $total = 0;
        do {
            $count = $this->updateTargetPasswords();
            $count += $this->updateSourcePasswords();
            $total += $count;
        } while($count !== 0);

        $this->logger->debugOrInfo(['Updated %s share(s)', $total], $total);
    }

    /**
     * @return int
     * @throws DoesNotExistException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updateTargetPasswords(): int {
        $total = 0;
        do {
            $shares = $this->shareService->findBySourceUpdated();
            $count  = count($shares);
            $total  += $count;

            foreach($shares as $share) {
                if($share->getTargetPassword() === null) continue;
                /** @var PasswordRevision $revision */
                $revision = $this->createNewPasswordRevision($share->getSourcePassword(), $share->getTargetPassword());

                /** @var Password $password */
                $password = $this->passwordService->findByUuid($share->getTargetPassword());
                $password->setEditable($share->isEditable());
                $this->passwordService->setRevision($password, $revision);

                $share->setTargetUpdated(false);
                $share->setSourceUpdated(false);
                $this->shareService->save($share);

                if(!$share->isShareable() && $password->hasShares()) {
                    $subShares = $this->shareService->findBySourcePassword($password->getUuid());
                    foreach($subShares as $subShare) {
                        $this->shareService->delete($subShare);
                    }
                    $this->deleteOrphanedTargetPasswords();
                    break;
                }

                if(!$share->isEditable()) {
                    $subShares = $this->shareService->findBySourcePassword($password->getUuid());
                    foreach($subShares as $subShare) {
                        if($subShare->isEditable()) {
                            $subShare->setEditable(false);
                            $subShare->setSourceUpdated(true);
                            $this->shareService->save($subShare);
                        }
                    }
                }
            }
        } while($count !== 0);

        return $total;
    }

    /**
     * @return int
     * @throws DoesNotExistException
     * @throws \Exception
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    protected function updateSourcePasswords(): int {
        $total = 0;
        do {
            $shares = $this->shareService->findByTargetUpdated();
            $count  = count($shares);
            $total  += $count;

            foreach($shares as $share) {
                if($share->isEditable()) {
                    $revision = $this->createNewPasswordRevision($share->getTargetPassword(), $share->getSourcePassword());

                    $password = $this->passwordService->findByUuid($share->getSourcePassword());
                    $this->passwordService->setRevision($password, $revision);
                }

                $share->setTargetUpdated(false);
                $share->setSourceUpdated(false);
                $this->shareService->save($share);
            }
        } while($count !== 0);

        return $total;
    }

    /**
     * @param string $sourceUuid
     * @param string $targetUuid
     *
     * @return PasswordRevision
     * @throws \Exception
     */
    protected function createNewPasswordRevision(string $sourceUuid, string $targetUuid): PasswordRevision {
        /** @var PasswordRevision $sourceRevision */
        $sourceRevision  = $this->passwordRevisionService->findCurrentRevisionByModel($sourceUuid, true);
        $currentRevision = $this->passwordRevisionService->findCurrentRevisionByModel($targetUuid, true);

        /** @var PasswordRevision $newRevision */
        $newRevision = $this->passwordRevisionService->clone($currentRevision, [
            'password'     => $sourceRevision->getPassword(),
            'username'     => $sourceRevision->getUsername(),
            'cseKey'       => $sourceRevision->getCseKey(),
            'cseType'      => $sourceRevision->getCseType(),
            'hash'         => $sourceRevision->getHash(),
            'label'        => $sourceRevision->getLabel(),
            'url'          => $sourceRevision->getUrl(),
            'notes'        => $sourceRevision->getNotes(),
            'customFields' => $sourceRevision->getCustomFields(),
            'status'       => $sourceRevision->getStatus(),
        ]);

        return $this->passwordRevisionService->save($newRevision);
    }

    /**
     *
     */
    protected function notifyUsers(): void {
        foreach($this->notifications['created'] as $receiver => $owners) {
            $this->notificationService->sendShareCreatedNotification($receiver, $owners);
            $this->mailService->sendShareCreateMail($receiver, $owners);
        }
        foreach($this->notifications['loop'] as $user => $amount) {
            $this->notificationService->sendShareLoopNotification($user, $amount);
        }
    }

    /**
     * @return bool
     */
    protected function canExecute(): bool {
        $this->config->clearCache();

        return $this->environment->getRunType() === EnvironmentService::TYPE_CRON &&
               $this->config->getAppValue(self::EXECUTION_TIMESTAMP, 0) < strtotime('-2 hours');
    }
}