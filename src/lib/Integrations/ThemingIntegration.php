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

use OC;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Theming\Capabilities;
use OCA\Theming\ThemingDefaults;
use OCP\IURLGenerator;

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
        protected IURLGenerator        $urlGenerator
    ) {
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return $this->config->isAppEnabled('theming') && class_exists(ThemingDefaults::class);
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
        $logoUrl = $this->getCapability('logoUrl');

        return $logoUrl ?? $this->getThemingDefaults()->getLogo();
    }

    /**
     * @return string
     */
    public function getName(): string {
        $color = $this->getCapability('name');

        return $color ?? $this->getThemingDefaults()->getName();
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

        return $backgroundColor ?? $this->getThemingDefaults()->getColorBackground();
    }

    /**
     * @return string
     */
    public function getBackgroundImage(): string {
        $userBackground = $this->getCapability('background');

        return $userBackground ?? $this->getThemingDefaults()->getBackground();
    }

    /**
     * @return ThemingDefaults
     */
    protected function getThemingDefaults(): ThemingDefaults {
        return \OC::$server->get(ThemingDefaults::class);
    }

    /**
     * @param string $capability
     * @return mixed|null
     */
    protected function getCapability(string $capability): mixed {
        if (!class_exists(Capabilities::class)) {
            return null;
        }


        if (!$this->capabilities) {
            $capabilities = OC::$server->get(Capabilities::class);

            $this->capabilities = $capabilities->getCapabilities()['theming'];
        }

        return $this->capabilities[$capability] ?? null;
    }
}