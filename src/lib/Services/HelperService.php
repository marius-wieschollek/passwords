<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Helper\Image\AbstractImageHelper;
use OCA\Passwords\Helper\Image\AutoImageHelper;
use OCA\Passwords\Helper\Image\GdHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\Image\ImaginaryHelper;
use OCA\Passwords\Provider\SecurityCheck\BigDbPlusHibpSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\BigLocalDbSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\HaveIBeenPwnedProvider;
use OCA\Passwords\Provider\SecurityCheck\SecurityCheckProviderInterface;
use OCA\Passwords\Provider\SecurityCheck\SmallLocalDbSecurityCheckProvider;
use OCA\Passwords\Provider\Words\AutoWordsProvider;
use OCA\Passwords\Provider\Words\LeipzigCorporaProvider;
use OCA\Passwords\Provider\Words\LocalWordsProvider;
use OCA\Passwords\Provider\Words\RandomCharactersProvider;
use OCA\Passwords\Provider\Words\SnakesWordsProvider;
use OCA\Passwords\Provider\Words\WordsProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class HelperService
 *
 * @package OCA\Passwords\Services
 */
class HelperService {

    const string PREVIEW_SCREEN_SHOT_MACHINE = 'ssm';
    const string PREVIEW_SCREEN_SHOT_LAYER   = 'ssl';
    const string PREVIEW_BROW_SHOT         = 'bws';
    const string PREVIEW_PAGERES    = 'pageres';
    const string PREVIEW_SCREEENLY = 'screeenly';
    const string PREVIEW_DEFAULT   = 'default';

    const string FAVICON_BESTICON        = 'bi';
    const string FAVICON_FAVICON_GRABBER = 'fg';
    const string FAVICON_DUCK_DUCK_GO    = 'ddg';
    const string FAVICON_GOOGLE       = 'gl';
    const string FAVICON_LOCAL  = 'local';
    const string FAVICON_DEFAULT = 'default';

    const string WORDS_LOCAL  = 'local';
    const string WORDS_RANDOM = 'random';
    const string WORDS_SNAKES = 'wo4snakes';
    const string WORDS_LEIPZIG = 'leipzig';
    const string WORDS_AUTO    = 'auto';

    const string SECURITY_BIGDB_HIBP = 'bigdb+hibp';
    const string SECURITY_BIG_LOCAL  = 'bigdb';
    const string SECURITY_SMALL_LOCAL = 'smalldb';
    const string SECURITY_HIBP        = 'hibp';

    const string IMAGES_AUTO    = 'auto';
    const string IMAGES_IMAGICK = 'imagick';
    const string IMAGES_GDLIB   = 'gdlib';
    const string IMAGES_IMAGINARY = 'imaginary';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * FaviconService constructor.
     *
     * @param ConfigurationService $config
     * @param ContainerInterface        $container
     */
    public function __construct(ConfigurationService $config, ContainerInterface $container) {
        $this->config    = $config;
        $this->container = $container;
    }

    /**
     * @param string|null $service
     *
     * @return AbstractImageHelper
     */
    public function getImageHelper(?string $service = null): AbstractImageHelper {
        if($service === null) $service = $this->config->getAppValue('service/images', self::IMAGES_AUTO);

        return match ($service) {
            self::IMAGES_IMAGICK => $this->container->get(ImagickHelper::class),
            self::IMAGES_GDLIB => $this->container->get(GdHelper::class),
            self::IMAGES_IMAGINARY => $this->container->get(ImaginaryHelper::class),
            default => $this->container->get(AutoImageHelper::class),
        };
    }

    /**
     * @param string|null $service
     *
     * @return WordsProviderInterface
     */
    public function getWordsHelper(?string $service = null): WordsProviderInterface {
        if($service === null) $service = $this->config->getAppValue('service/words', HelperService::WORDS_AUTO);

        return match ($service) {
            self::WORDS_LOCAL => $this->container->get(LocalWordsProvider::class),
            self::WORDS_LEIPZIG => $this->container->get(LeipzigCorporaProvider::class),
            self::WORDS_SNAKES => $this->container->get(SnakesWordsProvider::class),
            self::WORDS_RANDOM => $this->container->get(RandomCharactersProvider::class),
            default => $this->container->get(AutoWordsProvider::class),
        };
    }

    /**
     * @param string|null $service
     *
     * @depreacted without replacement
     *
     * @return SecurityCheckProviderInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getSecurityHelper(?string $service = null): SecurityCheckProviderInterface {
        $service = $this->config->getAppValue('service/security', $service ?? self::SECURITY_HIBP);

        return match ($service) {
            self::SECURITY_BIG_LOCAL => $this->container->get(BigLocalDbSecurityCheckProvider::class),
            self::SECURITY_SMALL_LOCAL => $this->container->get(SmallLocalDbSecurityCheckProvider::class),
            self::SECURITY_BIGDB_HIBP => $this->container->get(BigDbPlusHibpSecurityCheckProvider::class),
            default => $this->container->get(HaveIBeenPwnedProvider::class),
        };
    }
}
