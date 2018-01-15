<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 05.01.18
 * Time: 21:02
 */

namespace OCA\Passwords\Cron;

use OC\BackgroundJob\TimedJob;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Notification\Notifier;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Notification\IManager;

/**
 * Class SynchronizeShares
 *
 * @package OCA\Passwords\Cron
 *
 * @TODO    check for deleted users
 * @TODO    check if not shareable shares were shared
 */
class SynchronizeShares extends TimedJob {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var IManager
     */
    protected $notificationManager;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * SynchronizeShares constructor.
     *
     * @param LoggingService          $logger
     * @param ShareService            $shareService
     * @param IManager                $notificationManager
     * @param PasswordService         $passwordService
     * @param PasswordRevisionService $passwordRevisionService
     */
    public function __construct(
        LoggingService $logger,
        ShareService $shareService,
        IManager $notificationManager,
        PasswordService $passwordService,
        PasswordRevisionService $passwordRevisionService
    ) {
        // Run always
        $this->setInterval(1);

        $this->logger                  = $logger;
        $this->shareService            = $shareService;
        $this->passwordService         = $passwordService;
        $this->notificationManager     = $notificationManager;
        $this->passwordRevisionService = $passwordRevisionService;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function run($argument): void {
        $this->deleteOrphanedTargetPasswords();
        $this->deleteExpiredShares();
        $this->createNewShares();
        $this->removeSharedAttribute();
        $this->updatePasswords();
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

        $this->logger->info(['Deleted %s orphaned password(s)', $total]);
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

        $this->logger->info(['Deleted %s expired share(s)', $total]);
    }

    /**
     * @throws \Exception
     */
    protected function createNewShares(): void {
        $shares = $this->shareService->findNew();

        foreach($shares as $share) {
            if($this->shareLineageHasLoop($share)) {
                $this->shareService->delete($share);

                $this->sendLoopNotification($share);
                continue;
            }

            /** @var Password $model */
            $model = $this->passwordService->create();
            $model->setUserId($share->getReceiver());
            $model->setShareId($share->getUuid());
            $model->setEditable($share->getEditable());

            /** @var PasswordRevision $sourceRevision */
            $sourceRevision = $this->passwordRevisionService->findCurrentRevisionByModel($share->getSourcePassword(), true);
            $revision       = $this->passwordRevisionService->create(
                $model->getUuid(),
                $sourceRevision->getPassword(),
                $sourceRevision->getUsername(),
                $sourceRevision->getCseType(),
                $sourceRevision->getHash(),
                $sourceRevision->getLabel(),
                $sourceRevision->getUrl(),
                $sourceRevision->getNotes(),
                FolderService::BASE_FOLDER_UUID,
                time(),
                false,
                false,
                false
            );
            $revision->setUserId($share->getReceiver());

            $this->passwordRevisionService->save($revision);
            $this->passwordService->setRevision($model, $revision);

            $share->setTargetPassword($model->getUuid());
            $share->setSourceUpdated(false);
            $this->shareService->save($share);
        }

        $this->logger->info(['Created %s new share(s)', count($shares)]);
    }

    /**
     * @param Share $share
     *
     * @return bool
     * @throws MultipleObjectsReturnedException
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
            if($password->getUserId() === $share->getReceiver()) return true;
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

        $this->logger->info(['Removed shared attribute from %s password(s)', $total]);
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

        $this->logger->info(['Updated %s share(s)', $total]);
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
                $revision = $this->createNewPasswordRevision($share->getSourcePassword(), $share->getTargetPassword());

                /** @var Password $password */
                $password = $this->passwordService->findByUuid($share->getTargetPassword());
                $password->setEditable($share->isEditable());
                $this->passwordService->setRevision($password, $revision);

                $share->setTargetUpdated(false);
                $share->setSourceUpdated(false);
                $this->shareService->save($share);
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
     * @return PasswordRevision|EntityInterface
     * @throws \Exception
     */
    protected function createNewPasswordRevision(string $sourceUuid, string $targetUuid): PasswordRevision {
        /** @var PasswordRevision $sourceRevision */
        $sourceRevision  = $this->passwordRevisionService->findCurrentRevisionByModel($sourceUuid, true);
        $currentRevision = $this->passwordRevisionService->findCurrentRevisionByModel($targetUuid, true);

        /** @var PasswordRevision $newRevision */
        $newRevision = $this->passwordRevisionService->clone($currentRevision, [
            'password' => $sourceRevision->getPassword(),
            'username' => $sourceRevision->getUsername(),
            'cseType'  => $sourceRevision->getCseType(),
            'hash'     => $sourceRevision->getHash(),
            'label'    => $sourceRevision->getLabel(),
            'url'      => $sourceRevision->getUrl(),
            'notes'    => $sourceRevision->getNotes(),
            'status'   => $sourceRevision->getStatus(),
        ]);

        return $this->passwordRevisionService->save($newRevision);
    }

    /**
     * @param $share
     */
    protected function sendLoopNotification(Share $share): void {
        $notification = $this->notificationManager->createNotification();
        $notification->setApp(Application::APP_NAME)
                     ->setUser($share->getUserId())
                     ->setSubject(Notifier::NOTIFICATION_SHARE_LOOP)
                     ->setObject('receiver', $share->getReceiver())
                     ->setDateTime(new \DateTime());
        $this->notificationManager->notify($notification);
    }
}