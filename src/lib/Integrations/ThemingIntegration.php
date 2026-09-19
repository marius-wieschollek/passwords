<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Integrations;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Theming\ThemingColorHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Theming\Capabilities;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\ThemingDefaults;
use OCP\IURLGenerator;
use Psr\Container\ContainerInterface;

/**
 *
 */
class ThemingIntegration {

    protected ?array $capabilities = null;

    /**
     * @param ConfigurationService $config
     * @param IURLGenerator        $urlGenerator
     */
    public function __construct(
        protected ConfigurationService $config,
        protected IURLGenerator        $urlGenerator,
        protected ThemingColorHelper   $colorHelper,
        protected ContainerInterface   $container,
    ) {
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return $this->config->isAppEnabled('theming') && $this->container->has(ThemingDefaults::class);
    }

    /**
     * @return string
     */
    public function getFolderIcon(): string {
        return $this->urlGenerator->linkToRouteAbsolute(
            'theming.Icon.getThemedIcon',
            [
                'app'   => 'core',
                'image' => 'filetypes/folder.svg',
                'v'     => $this->getCapability('cacheBuster')
            ]
        );
    }

    /**
     * @return string
     */
    public function getAppIcon(): string {
        return $this->urlGenerator->linkToRouteAbsolute(
            'theming.Icon.getThemedIcon',
            [
                'app'   => Application::APP_NAME,
                'image' => 'app-themed.svg',
                'v'     => $this->getCapability('cacheBuster')
            ]
        );
    }

    /**
     * @return string
     */
    public function getLogoIcon(): string {
        $logoUrl = $this->getCapability('logo');

        return $logoUrl ?? $this->urlGenerator->getAbsoluteURL($this->getThemingDefaults()->getLogo());
    }

    /**
     * @return string
     */
    public function getName(): string {
        $name = $this->getCapability('name');

        return $name ?? $this->getThemingDefaults()->getName();
    }

    /**
     * @return string
     */
    public function getTextColorPrimary(): string {
        $color = $this->getCapability('color-text');

        return $color ?? $this->getThemingDefaults()->getTextColorPrimary();
    }

    /**
     * @return string
     */
    public function getColorPrimary(): string {
        $color = $this->getCapability('color');

        return $color ?? $this->getThemingDefaults()->getColorPrimary();
    }

    /**
     * @return string
     */
    public function getColorBackground(): string {
        $backgroundColor = $this->getCapability('background-text');

        if(!empty($backgroundColor)) {
            return $backgroundColor;
        }

        return $this->colorHelper->getTextColor($this->getThemingDefaults()->getColorBackground());
    }

    /**
     * @return string
     */
    public function getBackgroundImage(): string {
        if($this->getCapability('background-plain') === false) {
            $userBackground = $this->getCapability('background');

            if($userBackground) {
                if(parse_url($userBackground, PHP_URL_SCHEME) === null) {
                    $userBackground = $this->urlGenerator->getAbsoluteURL($userBackground);
                }

                return $userBackground;
            }
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->linkTo(
                \OCA\Theming\AppInfo\Application::APP_ID,
                'img/background/'.BackgroundService::DEFAULT_BACKGROUND_IMAGE
            )
        );
    }

    /**
     * @return ThemingDefaults
     */
    protected function getThemingDefaults(): ThemingDefaults {
        return $this->container->get(ThemingDefaults::class);
    }

    /**
     * @param string $capability
     *
     * @return mixed|null
     */
    protected function getCapability(string $capability): mixed {
        if(!$this->container->has(Capabilities::class)) {
            return null;
        }

        if($this->capabilities === null) {
            /** @var \OCA\Theming\Capabilities $capabilities */
            $capabilities = $this->container->get(Capabilities::class);

            $this->capabilities = $capabilities->getCapabilities()['theming'] ?? [];
        }

        return $this->capabilities[ $capability ] ?? null;
    }
}