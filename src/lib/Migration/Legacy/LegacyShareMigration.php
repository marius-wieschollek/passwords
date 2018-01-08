<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 08.01.18
 * Time: 22:09
 */

namespace OCA\Passwords\Migration\Legacy;

use OCA\Passwords\Db\Legacy\LegacyShare;
use OCA\Passwords\Db\Legacy\LegacyShareMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IUserManager;
use OCP\Migration\IOutput;

/**
 * Class LegacyShareMigration
 *
 * @package OCA\Passwords\Migration\Legacy
 */
class LegacyShareMigration {

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var LegacyShareMapper
     */
    protected $shareMapper;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * LegacyShareMigration constructor.
     *
     * @param IUserManager      $userManager
     * @param ShareService      $shareService
     * @param LegacyShareMapper $shareMapper
     * @param PasswordService   $passwordService
     */
    public function __construct(
        IUserManager $userManager,
        ShareService $shareService,
        LegacyShareMapper $shareMapper,
        PasswordService $passwordService
    ) {
        $this->userManager     = $userManager;
        $this->shareMapper     = $shareMapper;
        $this->shareService    = $shareService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param IOutput $output
     * @param array   $passwords
     */
    public function migratePasswords(IOutput $output, array $passwords): void {
        $shares = $this->shareMapper->findAll();

        $count = count($shares);
        $output->info("Migrating shares (total: {$count})");
        $output->startProgress($count);
        foreach($shares as $share) {
            if(!isset($passwords[ $share->getSharekey() ])) continue;

            try {
                $this->migrateShare($share, $passwords[ $share->getSharekey() ]);
            } catch(\Throwable $e) {
                $output->warning(
                    "Failed migrating share #{$share->getId()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
    }

    /**
     * @param LegacyShare $share
     * @param string      $passwordId
     *
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    protected function migrateShare(LegacyShare $share, string $passwordId): void {
        if($this->userManager->get($share->getSharedto()) === null) return;

        /** @var Password $password */
        $password = $this->passwordService->findByUuid($passwordId);

        $share = $this->shareService->create($passwordId, $share->getSharedto(), 'user', true);
        $share->setUserId($password->getUserId());
        $this->shareService->save($share);

        $password->setHasShares(true);
        $this->passwordService->save($password);
    }
}