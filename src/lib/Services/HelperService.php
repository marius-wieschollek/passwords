<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Favicon\AbstractFaviconHelper;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Helper\Favicon\DefaultFaviconHelper;
use OCA\Passwords\Helper\Favicon\DuckDuckGoHelper;
use OCA\Passwords\Helper\Favicon\FaviconGrabberHelper;
use OCA\Passwords\Helper\Favicon\GoogleFaviconHelper;
use OCA\Passwords\Helper\Favicon\LocalFaviconHelper;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\Preview\AbstractPreviewHelper;
use OCA\Passwords\Helper\Preview\BrowshotPreviewHelper;
use OCA\Passwords\Helper\Preview\DefaultPreviewHelper;
use OCA\Passwords\Helper\Preview\PageresCliHelper;
use OCA\Passwords\Helper\Preview\ScreeenlyHelper;
use OCA\Passwords\Helper\Preview\ScreenShotLayerHelper;
use OCA\Passwords\Helper\Preview\ScreenShotMachineHelper;
use OCA\Passwords\Helper\Preview\WebshotHelper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\BigDbPlusHibpSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\BigLocalDbSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\HaveIBeenPwnedHelper;
use OCA\Passwords\Helper\SecurityCheck\SmallLocalDbSecurityCheckHelper;
use OCA\Passwords\Helper\Words\AbstractWordsHelper;
use OCA\Passwords\Helper\Words\LeipzigCorporaHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\RandomCharactersHelper;
use OCA\Passwords\Helper\Words\SnakesWordsHelper;
use OCP\AppFramework\IAppContainer;

/**
 * Class HelperService
 *
 * @package OCA\Passwords\Services
 */
class HelperService {

    const PREVIEW_SCREEN_SHOT_MACHINE = 'ssm';
    const PREVIEW_SCREEN_SHOT_LAYER   = 'ssl';
    const PREVIEW_BROW_SHOT           = 'bws';
    const PREVIEW_WEBSHOT             = 'ws';
    const PREVIEW_PAGERES             = 'pageres';
    const PREVIEW_SCREEENLY           = 'screeenly';
    const PREVIEW_DEFAULT             = 'default';

    const FAVICON_BESTICON        = 'bi';
    const FAVICON_FAVICON_GRABBER = 'fg';
    const FAVICON_DUCK_DUCK_GO    = 'ddg';
    const FAVICON_GOOGLE          = 'gl';
    const FAVICON_LOCAL           = 'local';
    const FAVICON_DEFAULT         = 'default';

    const WORDS_LOCAL   = 'local';
    const WORDS_RANDOM  = 'random';
    const WORDS_SNAKES  = 'wo4snakes';
    const WORDS_LEIPZIG = 'leipzig';

    const SECURITY_BIGDB_HIBP  = 'bigdb+hibp';
    const SECURITY_BIG_LOCAL   = 'bigdb';
    const SECURITY_SMALL_LOCAL = 'smalldb';
    const SECURITY_HIBP        = 'hibp';

    const IMAGES_IMAGICK = 'imagick';
    const IMAGES_GDLIB   = 'gdlib';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IAppContainer
     */
    protected $container;

    /**
     * FaviconService constructor.
     *
     * @param ConfigurationService $config
     * @param IAppContainer        $container
     */
    public function __construct(ConfigurationService $config, IAppContainer $container) {
        $this->config    = $config;
        $this->container = $container;
    }

    /**
     * @return AbstractImageHelper
     * @throws \OCP\AppFramework\QueryException
     */
    public function getImageHelper(): AbstractImageHelper {
        $service = self::getImageHelperName($this->config->getAppValue('service/images', self::IMAGES_IMAGICK));
        $class   = $service === self::IMAGES_IMAGICK ? ImagickHelper::class:GdHelper::class;

        return $this->container->query($class);
    }

    /**
     * @return AbstractPreviewHelper
     * @throws \OCP\AppFramework\QueryException
     */
    public function getWebsitePreviewHelper(): AbstractPreviewHelper {
        $service = $this->config->getAppValue('service/preview', self::PREVIEW_DEFAULT);

        switch($service) {
            case self::PREVIEW_PAGERES:
                return $this->container->query(PageresCliHelper::class);
            case self::PREVIEW_WEBSHOT:
                return $this->container->query(WebshotHelper::class);
            case self::PREVIEW_BROW_SHOT:
                return $this->container->query(BrowshotPreviewHelper::class);
            case self::PREVIEW_SCREEN_SHOT_LAYER:
                return $this->container->query(ScreenShotLayerHelper::class);
            case self::PREVIEW_SCREEN_SHOT_MACHINE:
                return $this->container->query(ScreenShotMachineHelper::class);
            case self::PREVIEW_SCREEENLY:
                return $this->container->query(ScreeenlyHelper::class);
            case self::PREVIEW_DEFAULT:
                return $this->container->query(DefaultPreviewHelper::class);
        }

        return $this->container->query(DefaultPreviewHelper::class);
    }

    /**
     * @return AbstractFaviconHelper
     * @throws \OCP\AppFramework\QueryException
     */
    public function getFaviconHelper(): AbstractFaviconHelper {
        $service = $this->config->getAppValue('service/favicon', self::FAVICON_DEFAULT);

        switch($service) {
            case self::FAVICON_BESTICON:
                return $this->container->query(BestIconHelper::class);
            case self::FAVICON_FAVICON_GRABBER:
                return $this->container->query(FaviconGrabberHelper::class);
            case self::FAVICON_DUCK_DUCK_GO:
                return $this->container->query(DuckDuckGoHelper::class);
            case self::FAVICON_GOOGLE:
                return $this->container->query(GoogleFaviconHelper::class);
            case self::FAVICON_LOCAL:
                return $this->container->query(LocalFaviconHelper::class);
            case self::FAVICON_DEFAULT:
                return $this->container->query(DefaultFaviconHelper::class);
        }

        return $this->container->query(DefaultFaviconHelper::class);
    }

    /**
     * @return AbstractWordsHelper
     * @throws \OCP\AppFramework\QueryException
     */
    public function getWordsHelper(): AbstractWordsHelper {
        $service = $this->config->getAppValue('service/words', $this->getDefaultWordsHelperName());

        switch($service) {
            case self::WORDS_LOCAL:
                return $this->container->query(LocalWordsHelper::class);
            case self::WORDS_LEIPZIG:
                return $this->container->query(LeipzigCorporaHelper::class);
            case self::WORDS_SNAKES:
                return $this->container->query(SnakesWordsHelper::class);
            case self::WORDS_RANDOM:
                return $this->container->query(RandomCharactersHelper::class);
        }

        return $this->container->query(RandomCharactersHelper::class);
    }

    /**
     * @return AbstractSecurityCheckHelper
     * @throws \OCP\AppFramework\QueryException
     */
    public function getSecurityHelper(): AbstractSecurityCheckHelper {
        $service = $this->config->getAppValue('service/security', self::SECURITY_HIBP);

        switch($service) {
            case self::SECURITY_HIBP:
                return $this->container->query(HaveIBeenPwnedHelper::class);
            case self::SECURITY_BIG_LOCAL:
                return $this->container->query(BigLocalDbSecurityCheckHelper::class);
            case self::SECURITY_SMALL_LOCAL:
                return $this->container->query(SmallLocalDbSecurityCheckHelper::class);
            case self::SECURITY_BIGDB_HIBP:
                return $this->container->query(BigDbPlusHibpSecurityCheckHelper::class);
        }

        return $this->container->query(HaveIBeenPwnedHelper::class);
    }

    /**
     * @return DefaultFaviconHelper
     * @throws \OCP\AppFramework\QueryException
     */
    public function getDefaultFaviconHelper(): DefaultFaviconHelper {
        return $this->container->query(DefaultFaviconHelper::class);
    }

    /**
     * @return string
     */
    public static function getDefaultWordsHelperName(): string {
        if(LocalWordsHelper::isAvailable()) {
            return self::WORDS_LOCAL;
        }
        if(RandomCharactersHelper::isAvailable()) {
            return self::WORDS_RANDOM;
        }
        if(SnakesWordsHelper::isAvailable()) {
            return self::WORDS_SNAKES;
        }

        return '';
    }

    /**
     * @param string $current
     *
     * @return string
     */
    public static function getImageHelperName(string $current = self::IMAGES_IMAGICK): string {
        if($current === self::IMAGES_IMAGICK && ImagickHelper::isAvailable()) return self::IMAGES_IMAGICK;

        return self::IMAGES_GDLIB;
    }
}