<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OC;
use OC_Defaults;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Theming\ThemingDefaults;
use OCA\Unsplash\ProviderHandler\Provider;
use OCA\Unsplash\Services\SettingsService;
use OCP\IURLGenerator;
use Throwable;

/**
 * Class ThemeSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ThemeSettingsHelper {

    /**
     * ThemeSettingsHelper constructor.
     *
     * @param ConfigurationService $config
     * @param OC_Defaults          $theming
     * @param IURLGenerator        $urlGenerator
     */
    public function __construct(
        protected ConfigurationService $config,
        protected OC_Defaults          $theming,
        protected IURLGenerator        $urlGenerator
    ) {
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    public function get(string $key) {
        switch($key) {
            case 'color':
            case 'color.primary':
                return $this->getColorPrimary();
            case 'color.text':
            case 'text.color':
                return $this->theming->getTextColorPrimary();
            case 'color.background':
                return $this->getBackgroundColor();
            case 'background':
                return $this->getBackgroundImage();
            case 'logo':
                return $this->urlGenerator->getAbsoluteURL($this->theming->getLogo());
            case 'label':
                return $this->theming->getEntity();
            case 'app.icon':
                return $this->getThemedAppIcon();
            case 'folder.icon':
                return $this->getFolderIcon();
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        return [
            'server.theme.color.primary'    => $this->getColorPrimary(),
            'server.theme.color.text'       => $this->get('color.text'),
            'server.theme.color.background' => $this->getBackgroundColor(),
            'server.theme.background'       => $this->getBackgroundImage(),
            'server.theme.logo'             => $this->get('logo'),
            'server.theme.label'            => $this->get('label'),
            'server.theme.app.icon'         => $this->getThemedAppIcon(),
            'server.theme.folder.icon'      => $this->getFolderIcon()
        ];
    }

    /**
     * @return string
     */
    protected function getFolderIcon(): string {
        if($this->config->isAppEnabled('theming')) {
            $version = $this->getCacheBuster();

            return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => 'core', 'image' => 'filetypes/folder.svg', 'v' => $version]);
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath('core', 'filetypes/folder.svg')
        );
    }

    /**
     * @return string
     */
    protected function getThemedAppIcon(): string {
        if($this->config->isAppEnabled('theming')) {
            $version = $this->getCacheBuster();

            return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => Application::APP_NAME, 'image' => 'app-themed.svg', 'v' => $version]);
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_NAME, 'app-themed.svg')
        );
    }

    /**
     * @return string
     */
    protected function getBackgroundImage(): string {
        try {
            if(method_exists($this->theming, 'isUserThemingDisabled') && !$this->theming->isUserThemingDisabled()) {
                if(\OC_Util::getVersion()[0] === 25) {
                    $userBackground = $this->config->getUserValue('background', '', null, 'theming');
                } else {
                    $userBackground = $this->config->getUserValue('background_image', '', null, 'theming');
                }

                if(!empty($userBackground) && !str_starts_with($userBackground, '#') && $userBackground !== 'disabled') {
                    if($userBackground === 'custom') {
                        return $this->urlGenerator->linkToRouteAbsolute('theming.userTheme.getBackground', ['v' => $this->getCacheBuster()]);
                    } else {
                        return $this->urlGenerator->getAbsoluteURL(
                            $this->urlGenerator->linkTo('theming', "img/background/{$userBackground}", ['v' => $this->getCacheBuster()])
                        );
                    }
                }
            }
        } catch(\Throwable $e) {
        }

        if($this->config->isAppEnabled('unsplash') && class_exists(SettingsService::class)) {
            try {
                $settings = OC::$server->get(SettingsService::class);

                return $settings->headerbackgroundLink(Provider::SIZE_NORMAL);
            } catch(\Throwable $e) {
            }
        }

        if(method_exists($this->theming, 'getBackground')) {
            return $this->urlGenerator->getAbsoluteURL($this->theming->getBackground());
        }

        if(\OC_Util::getVersion()[0] === 25) {
            return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'app-background.jpg'));
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->linkTo('theming', 'img/background/kamil-porembinski-clouds.jpg', ['v' => $this->getCacheBuster()])
        );
    }

    /**
     * @return string
     */
    protected function getBackgroundColor(): string {
        try {
            if(in_array($this->config->getUserValue('theme', 'none', null, 'accessibility'), ['themedark', 'dark'])) {
                return '#181818';
            }
        } catch(Throwable $e) {
        }

        if($this->config->isAppEnabled('breezedark')) {
            return '#31363b';
        }

        return '#ffffff';
    }

    /**
     * @return string
     */
    protected function getColorPrimary(): string {
        try {
            $userBackground = $this->config->getUserValue('background_color', '', null, 'theming');
            if(!empty($userBackground)) {
                return $userBackground;
            }
        } catch(\Throwable $e) {

        }

        if($this->config->isAppEnabled('breezedark')) {
            return '#3daee9';
        }

        return $this->theming->getColorPrimary();
    }

    /**
     * @return string|null
     */
    public function getCacheBuster(): ?string {
        $version = $this->config->getAppValue('cachebuster', '0', 'theming');

        if(method_exists($this->theming, 'isUserThemingDisabled') && !$this->theming->isUserThemingDisabled()) {
            try {
                return $version.'_'.$this->config->getUserValue('userCacheBuster', '0', null, 'theming');
            } catch(\Throwable $e) {
            }
        }

        return $version;
    }
}