<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IAvatarManager;
use OCP\IImage;
use OCP\IUserManager;

/**
 * Class AvatarService
 *
 * @package OCA\Passwords\Services
 */
class AvatarService {

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IAvatarManager
     */
    protected $avatarManager;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var FallbackIconGenerator
     */
    protected $fallbackIconGenerator;

    /**
     * AvatarService constructor.
     *
     * @param IUserManager          $userManager
     * @param IAvatarManager        $avatarManager
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     */
    public function __construct(
        IUserManager $userManager,
        IAvatarManager $avatarManager,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->fileCacheService      = $fileCacheService->getCacheService($fileCacheService::AVATAR_CACHE);
        $this->fallbackIconGenerator = $fallbackIconGenerator;
        $this->userManager           = $userManager;
        $this->avatarManager         = $avatarManager;
    }

    /**
     * @param string $userId
     * @param int    $size
     *
     * @return null|\OCP\Files\SimpleFS\ISimpleFile
     * @throws \Throwable
     */
    public function getAvatar(string $userId, int $size = 32): ?ISimpleFile {
        $size = $this->validateSize($size);

        $fileName = "{$userId}_{$size}.png";
        if($this->fileCacheService->hasFile($fileName)) {
            return $this->fileCacheService->getFile($fileName);
        }

        $user      = $this->userManager->get($userId);
        $imageData = null;
        if($user !== null) {
            $avatar = $this->avatarManager->getAvatar($userId);
            if($avatar->exists()) {
                $image = $avatar->get($size);
                if($image !== null && $image->valid()) $imageData = $this->getImage($image, $user->getDisplayName(), $size);
            } else {
                $imageData = $this->fallbackIconGenerator->createIcon($user->getDisplayName(), $size);
            }
        } else {
            $imageData = $this->fallbackIconGenerator->createIcon(strtolower($user), $size);
        }

        return $this->fileCacheService->putFile($fileName, $imageData);
    }

    /**
     * @param IImage $image
     * @param string $name
     *
     * @param int    $size
     *
     * @return string
     * @throws \Throwable
     */
    protected function getImage(IImage $image, string $name, int $size): string {
        ob_start();
        $resize = $image->resize($size);
        $export = imagepng($image->resource());
        $data   = ob_get_clean();

        if(!$resize || !$export) return $this->fallbackIconGenerator->createIcon($name, $size);

        return $data;
    }

    /**
     * @param int $size
     *
     * @return float|int
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