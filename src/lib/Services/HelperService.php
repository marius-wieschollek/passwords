<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 11.09.17
 * Time: 21:29
 */

namespace OCA\Passwords\Services;

use Gmagick;
use Imagick;
use OCA\Passwords\Helper\Favicon\AbstractFaviconHelper;
use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\PageShot\AbstractPageShotHelper;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\Words\AbstractWordsHelper;
use OCP\AppFramework\IAppContainer;

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

    const SECURITY_LOCAL        = 'local';
    const SECURITY_HBIP_ONLINE  = 'hbip';
    const SECURITY_HBIP_OFFLINE = 'hbip_offline';

    const IMAGES_IMAGICK = 'imagick';
    const IMAGES_GDLIB   = 'gdlib';

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var IAppContainer
     */
    protected $container;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * FaviconService constructor.
     *
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     * @param IAppContainer        $container
     */
    public function __construct(ConfigurationService $config, FileCacheService $fileCacheService, IAppContainer $container) {
        $this->config           = $config;
        $this->fileCacheService = $fileCacheService;
        $this->container        = $container;
    }

    /**
     * @return AbstractImageHelper
     */
    public function getImageHelper(): AbstractImageHelper {
        $service = $this->config->getAppValue('service/images', self::IMAGES_IMAGICK);

        if($service == self::IMAGES_IMAGICK && class_exists(Imagick::class) || class_exists(Gmagick::class)) {
            return $this->container->query('ImagickHelper');
        }

        return $this->container->query('GdHelper');
    }

    /**
     * @return AbstractPageShotHelper
     */
    public function getPageShotHelper(): AbstractPageShotHelper {
        $service = $this->config->getAppValue('service/pageshot', self::PAGESHOT_WKHTML);

        switch ($service) {
            case self::PAGESHOT_WKHTML:
                return $this->container->query('WkhtmlImageHelper');
            case self::PAGESHOT_SCREEN_SHOT_API:
                return $this->container->query('ScreenShotApiHelper');
            case self::PAGESHOT_SCREEN_SHOT_LAYER:
                return $this->container->query('ScreenShotLayerHelper');
            case self::PAGESHOT_SCREEN_SHOT_MACHINE:
                return $this->container->query('ScreenShotMachineHelper');
            case self::PAGESHOT_DEFAULT:
                return $this->container->query('DefaultPageShotHelper');
        }

        return $this->container->query('DefaultPageShotHelper');
    }

    /**
     * @return AbstractFaviconHelper
     */
    public function getFaviconHelper(): AbstractFaviconHelper {
        $service = $this->config->getAppValue('service/favicon', self::FAVICON_LOCAL);

        switch ($service) {
            case self::FAVICON_BETTER_IDEA:
                return $this->container->query('BetterIdeaHelper');
            case self::FAVICON_DUCK_DUCK_GO:
                return $this->container->query('DuckDuckGoHelper');
            case self::FAVICON_GOOGLE:
                return $this->container->query('GoogleFaviconHelper');
            case self::FAVICON_LOCAL:
                return $this->container->query('LocalFaviconHelper');
            case self::FAVICON_DEFAULT:
                return $this->container->query('DefaultFaviconHelper');
        }

        return $this->container->query('DefaultFaviconHelper');
    }

    /**
     * @return AbstractWordsHelper
     * @TODO support more services
     */
    public function getWordsHelper(): AbstractWordsHelper {
        $service = $this->config->getAppValue('service/words', self::WORDS_SNAKES);

        switch ($service) {
            case self::WORDS_LOCAL:
                return $this->container->query('LocalWordsHelper');
            case self::WORDS_SNAKES:
                return $this->container->query('SnakesWordsHelper');
        }

        return $this->container->query('SnakesWordsHelper');
    }

    /**
     * @return AbstractSecurityCheckHelper
     * @TODO support more services
     */
    public function getSecurityHelper(): AbstractSecurityCheckHelper {
        $service = $this->config->getAppValue('service/security', self::SECURITY_HBIP_ONLINE);

        switch ($service) {
            case self::SECURITY_HBIP_ONLINE:
                return $this->container->query('HbipOnlineHelper');
            case self::SECURITY_LOCAL:
                return $this->container->query('LocalSecurityCheckHelper');
        }

        return $this->container->query('HbipOnlineHelper');
    }
}