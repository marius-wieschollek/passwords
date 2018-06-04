<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Gmagick;
use Imagick;
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
use OCA\Passwords\Helper\Preview\DefaultPreviewHelper;
use OCA\Passwords\Helper\Preview\PageresCliHelper;
use OCA\Passwords\Helper\Preview\ScreenShotApiHelper;
use OCA\Passwords\Helper\Preview\ScreenShotMachineHelper;
use OCA\Passwords\Helper\Preview\WkhtmlImageHelper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\BigDbPlusHibpSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\BigLocalDbSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\HaveIBeenPwnedHelper;
use OCA\Passwords\Helper\SecurityCheck\SmallLocalDbSecurityCheckHelper;
use OCA\Passwords\Helper\Words\AbstractWordsHelper;
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
    const PREVIEW_SCREEN_SHOT_API     = 'ssa';
    const PREVIEW_WKHTML              = 'wkhtml';
    const PREVIEW_PAGERES             = 'pageres';
    const PREVIEW_DEFAULT             = 'default';

    const FAVICON_BESTICON        = 'bi';
    const FAVICON_FAVICON_GRABBER = 'fg';
    const FAVICON_DUCK_DUCK_GO    = 'ddg';
    const FAVICON_GOOGLE          = 'gl';
    const FAVICON_LOCAL           = 'local';
    const FAVICON_DEFAULT         = 'default';

    const WORDS_LOCAL  = 'local';
    const WORDS_RANDOM = 'random';
    const WORDS_SNAKES = 'wo4snakes';

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
     */
    public function getImageHelper(): AbstractImageHelper {
        $service = $this->config->getAppValue('service/images', self::IMAGES_IMAGICK);

        if($service == self::IMAGES_IMAGICK && class_exists(Imagick::class) || class_exists(Gmagick::class)) {
            return $this->container->query(ImagickHelper::class);
        }

        return $this->container->query(GdHelper::class);
    }

    /**
     * @return AbstractPreviewHelper
     */
    public function getWebsitePreviewHelper(): AbstractPreviewHelper {
        $service = $this->config->getAppValue('service/preview', self::PREVIEW_DEFAULT);

        switch($service) {
            case self::PREVIEW_WKHTML:
                return $this->container->query(WkhtmlImageHelper::class);
            case self::PREVIEW_PAGERES:
                return $this->container->query(PageresCliHelper::class);
            case self::PREVIEW_SCREEN_SHOT_API:
                return $this->container->query(ScreenShotApiHelper::class);
            case self::PREVIEW_SCREEN_SHOT_MACHINE:
                return $this->container->query(ScreenShotMachineHelper::class);
            case self::PREVIEW_DEFAULT:
                return $this->container->query(DefaultPreviewHelper::class);
        }

        return $this->container->query(DefaultPreviewHelper::class);
    }

    /**
     * @return AbstractFaviconHelper
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
     */
    public function getWordsHelper(): AbstractWordsHelper {
        $service = $this->config->getAppValue('service/words', self::WORDS_RANDOM);

        switch($service) {
            case self::WORDS_LOCAL:
                return $this->container->query(LocalWordsHelper::class);
            case self::WORDS_SNAKES:
                return $this->container->query(SnakesWordsHelper::class);
            case self::WORDS_RANDOM:
                return $this->container->query(RandomCharactersHelper::class);
        }

        return $this->container->query(RandomCharactersHelper::class);
    }

    /**
     * @return AbstractSecurityCheckHelper
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
     */
    public function getDefaultFaviconHelper(): DefaultFaviconHelper {
        return $this->container->query(DefaultFaviconHelper::class);
    }
}