<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 21:29
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Favicon\AbstractFaviconHelper;
use OCA\Passwords\Helper\Favicon\BetterIdeaHelper;
use OCA\Passwords\Helper\Favicon\DefaultHelper as DefaultFaviconHelper;
use OCA\Passwords\Helper\Favicon\DuckDuckGoHelper;
use OCA\Passwords\Helper\Favicon\GoogleHelper;
use OCA\Passwords\Helper\Favicon\LocalFaviconHelper;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\PageShot\AbstractPageShotHelper;
use OCA\Passwords\Helper\PageShot\DefaultHelper as DefaultPageShotHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotApiHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotLayerHelper;
use OCA\Passwords\Helper\PageShot\ScreenShotMachineHelper;
use OCA\Passwords\Helper\PageShot\WkhtmlImageHelper;
use OCA\Passwords\Helper\Words\AbstractWordsHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\SnakesWordsHelper;

/**
 * Class HelperService
 *
 * @package OCA\Passwords\Services
 */
class HelperService {

    const PAGESHOT_SCREEN_SHOT_LAYER   = 'ssl';
    const PAGESHOT_SCREEN_SHOT_MACHINE = 'ssm';
    const PAGESHOT_SCREEN_SHOT_API     = 'ssa';
    const PAGESHOT_WKHTML              = 'wkhtml';
    const PAGESHOT_DEFAULT             = 'default';

    const FAVICON_BETTER_IDEA  = 'bi';
    const FAVICON_DUCK_DUCK_GO = 'ddg';
    const FAVICON_GOOGLE       = 'gl';
    const FAVICON_LOCAL        = 'local';
    const FAVICON_DEFAULT      = 'default';

    const WORDS_LOCAL  = 'local';
    const WORDS_SNAKES = 'wo4snakes';

    const IMAGES_IMAGICK = 'imagick';
    const IMAGES_GDLIB   = 'gdlib';
    /**
     * @var FileCacheService
     */
    private $fileCacheService;

    /**
     * FaviconService constructor.
     *
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(ConfigurationService $config, FileCacheService $fileCacheService) {
        $this->config           = $config;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @return AbstractImageHelper
     */
    public function getImageHelper(): AbstractImageHelper {
        $service = $this->config->getAppValue('service/images', self::IMAGES_IMAGICK);

        if($service == self::IMAGES_IMAGICK && class_exists(\Imagick::class) || class_exists(\Gmagick::class)) {
            return new ImagickHelper();
        }

        return new GdHelper();
    }

    /**
     * @return AbstractPageShotHelper
     */
    public function getPageShotHelper(): AbstractPageShotHelper {
        $service          = $this->config->getAppValue('service/pageshot', self::PAGESHOT_WKHTML);
        $fileCacheService = clone $this->fileCacheService;
        $fileCacheService->setDefaultCache($fileCacheService::PAGESHOT_CACHE);

        switch ($service) {
            case self::PAGESHOT_WKHTML:
                return new WkhtmlImageHelper($fileCacheService);
            case self::PAGESHOT_SCREEN_SHOT_API:
                return new ScreenShotApiHelper($fileCacheService, $this->config);
            case self::PAGESHOT_SCREEN_SHOT_LAYER:
                return new ScreenShotLayerHelper($fileCacheService, $this->config);
            case self::PAGESHOT_SCREEN_SHOT_MACHINE:
                return new ScreenShotMachineHelper($fileCacheService, $this->config);
            case self::PAGESHOT_DEFAULT:
                return new DefaultPageShotHelper($fileCacheService);
        }

        return new DefaultPageShotHelper($fileCacheService);
    }

    /**
     * @return AbstractFaviconHelper
     */
    public function getFaviconHelper(): AbstractFaviconHelper {
        $service          = $this->config->getAppValue('service/favicon', self::FAVICON_LOCAL);
        $fileCacheService = clone $this->fileCacheService;
        $fileCacheService->setDefaultCache($fileCacheService::FAVICON_CACHE);

        switch ($service) {
            case self::FAVICON_BETTER_IDEA:
                return new BetterIdeaHelper($fileCacheService);
            case self::FAVICON_DUCK_DUCK_GO:
                return new DuckDuckGoHelper($fileCacheService);
            case self::FAVICON_GOOGLE:
                return new GoogleHelper($fileCacheService);
            case self::FAVICON_LOCAL:
                return new LocalFaviconHelper($fileCacheService, $this->getImageHelper());
            case self::FAVICON_DEFAULT:
                return new DefaultFaviconHelper($fileCacheService);
        }

        return new DefaultFaviconHelper($fileCacheService);
    }

    /**
     * @return AbstractWordsHelper
     * @TODO support more services
     */
    public function getWordsHelper(): AbstractWordsHelper {
        $service = $this->config->getAppValue('service/words', self::WORDS_SNAKES);

        switch ($service) {
            case self::WORDS_LOCAL:
                return new LocalWordsHelper();
            case self::WORDS_SNAKES:
                return new SnakesWordsHelper();
        }

        return new LocalWordsHelper();
    }
}