<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OC\Avatar\GuestAvatar;
use OCA\Passwords\Helper\Compatibility\ServerVersion;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class AvatarService
 *
 * @package OCA\Passwords\Services
 */
class AvatarService {

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * AvatarService constructor.
     *
     * @param LoggerInterface  $logger
     * @param IConfig          $config
     * @param IUserManager     $userManager
     * @param IAvatarManager   $avatarManager
     * @param FileCacheService $fileCacheService
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected IConfig         $config,
        protected IUserManager    $userManager,
        protected IAvatarManager  $avatarManager,
        FileCacheService          $fileCacheService
    ) {
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::AVATAR_CACHE);
    }

    /**
     * @param string $userId
     * @param int    $size
     *
     * @return null|ISimpleFile
     * @throws Throwable
     */
    public function getAvatar(string $userId, int $size = 32): ?ISimpleFile {
        $size = $this->validateSize($size);

        $user = $this->userManager->get($userId);
        if($user !== null) {
            $avatar = $this->avatarManager->getAvatar($userId);

            return $avatar->getFile($size);
        } else {
            if(ServerVersion::getMajorVersion() < 32) {
                return (new GuestAvatar($userId, $this->logger))->getFile($size);
            } else {
                return (new GuestAvatar($userId, $this->config, $this->logger))->getFile($size);
            }
        }
    }

    /**
     * @param int $size
     *
     * @return int
     */
    protected function validateSize(int $size): int {
        $size = round($size / 8) * 8;
        if($size < 16) {
            $size = 16;
        } else if($size > 256) {
            $size = 256;
        }

        return $size;
    }
}