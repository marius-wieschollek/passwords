<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\Preview\ScreenShotApiHelper;
use OCA\Passwords\Helper\Preview\ScreenShotMachineHelper;
use OCA\Passwords\Helper\Preview\WebshotHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\RandomCharactersHelper;
use OCA\Passwords\Helper\Words\SnakesWordsHelper;
use OCA\Passwords\Services\HelperService;

/**
 * Class ServiceSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class ServiceSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var
     */
    protected $scope = 'service';

    /**
     * @var array
     */
    protected $keys
        = [
            'security' => 'service/security',
            'words'    => 'service/words',
            'images'   => 'service/images',
            'preview'  => 'service/preview',
            'favicon'  => 'service/favicon'
        ];

    /**
     * @var array
     */
    protected $defaults
        = [
            'security'    => HelperService::SECURITY_HIBP,
            'preview'     => HelperService::PREVIEW_DEFAULT,
            'favicon'     => HelperService::FAVICON_DEFAULT,
            'preview.api' => '',
            'favicon.api' => ''
        ];

    /**
     * @return array
     */
    public function list(): array {
        try {
            return [
                $this->get('words'),
                $this->get('images'),
                $this->get('favicon'),
                $this->get('preview'),
                $this->get('security'),
                $this->get('favicon.api'),
                $this->get('preview.api')
            ];
        } catch(ApiException $e) {
            return [];
        }
    }

    /**
     * @param $key
     *
     * @return array
     * @throws ApiException
     */
    public function get(string $key): array {
        switch($key) {
            case 'words':
                return $this->getGenericSetting('words');
            case 'images':
                return $this->getGenericSetting('images');
            case 'favicon':
                return $this->getGenericSetting('favicon');
            case 'preview':
                return $this->getGenericSetting('preview');
            case 'security':
                return $this->getGenericSetting('security');
            case 'favicon.api':
                return $this->getFaviconApiSetting();
            case 'preview.api':
                return $this->getPreviewApiSetting();
        }

        throw new ApiException('Unknown setting identifier', 400);
    }

    /**
     * @param string $setting
     *
     * @return string
     * @throws ApiException
     */
    protected function getSettingKey(string $setting): string {
        switch($setting) {
            case 'preview.api':
                return $this->getPreviewApiSettingKey();
            case 'favicon.api':
                return $this->getFaviconApiSettingKey();
        }

        return parent::getSettingKey($setting);
    }

    /**
     * @param string $setting
     *
     * @return string
     * @throws ApiException
     */
    protected function getSettingDefault(string $setting) {
        switch($setting) {
            case 'words':
                return HelperService::getDefaultWordsHelperName();
            case 'images':
                return HelperService::getImageHelperName();
        }

        return parent::getSettingDefault($setting);
    }

    /**
     * @return array
     */
    protected function getFaviconApiSetting(): array {
        $default = $this->getFaviconApiSettingDefault();
        $value   = $this->config->getAppValue(
            $this->getFaviconApiSettingKey(),
            $default
        );

        return $this->generateSettingArray(
            'favicon.api',
            $value,
            [],
            $default,
            'string',
            [
                'service.favicon' => [HelperService::FAVICON_BESTICON]
            ]
        );
    }

    /**
     * @return string
     */
    protected function getFaviconApiSettingKey(): string {
        $service = $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT);

        if($service === HelperService::FAVICON_BESTICON) {
            return BestIconHelper::BESTICON_CONFIG_KEY;
        }

        return 'service/favicon/api';
    }

    /**
     * @return string
     */
    protected function getFaviconApiSettingDefault(): string {
        $service = $this->config->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT);

        if($service === HelperService::FAVICON_BESTICON) {
            return BestIconHelper::BESTICON_DEFAULT_URL;
        }

        return '';
    }

    /**
     * @return array
     */
    protected function getPreviewApiSetting(): array {
        $value = $this->config->getAppValue($this->getPreviewApiSettingKey(), '');

        return $this->generateSettingArray(
            'preview.api',
            $value,
            [],
            '',
            'string',
            [
                'service.preview' => [
                    HelperService::PREVIEW_SCREEN_SHOT_API,
                    HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                    HelperService::PREVIEW_WEBSHOT
                ]
            ]
        );
    }

    /**
     * @return string
     */
    protected function getPreviewApiSettingKey(): string {
        $service = $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

        if($service === HelperService::PREVIEW_SCREEN_SHOT_API) {
            return ScreenShotApiHelper::SSA_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_SCREEN_SHOT_MACHINE) {
            return ScreenShotMachineHelper::SSM_API_CONFIG_KEY;
        }

        if($service === HelperService::PREVIEW_WEBSHOT) {
            return WebshotHelper::WEBSHOT_CONFIG_KEY;
        }

        return 'service/preview/api';
    }

    /**
     * @return array
     */
    protected function getSecurityOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::SECURITY_HIBP,
                'Have I been pwned? (recommended)'
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_BIG_LOCAL,
                '10 Million Passwords (Local)'
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_SMALL_LOCAL,
                '1 Million Passwords (Local)'
            ),
            $this->generateOptionArray(
                HelperService::SECURITY_BIGDB_HIBP,
                '10Mio Passwords & Hibp?'
            )
        ];
    }

    /**
     * @return array
     */
    protected function getWordsOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::WORDS_LOCAL,
                'Local dictionary',
                LocalWordsHelper::isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_SNAKES,
                'watchout4snakes.com (recommended)',
                SnakesWordsHelper::isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::WORDS_RANDOM,
                'Random Characters',
                RandomCharactersHelper::isAvailable()
            )
        ];
    }

    /**
     * @return array
     */
    protected function getFaviconOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::FAVICON_LOCAL,
                'Local analyzer'
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_BESTICON,
                'Besticon (recommended)'
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_FAVICON_GRABBER,
                'favicongrabber.com'
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_DUCK_DUCK_GO,
                'DuckDuckGo'
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_GOOGLE,
                'Google'
            ),
            $this->generateOptionArray(
                HelperService::FAVICON_DEFAULT,
                'None'
            )
        ];
    }

    /**
     * @return array
     */
    protected function getPreviewOptions(): array {
        $current = $this->config->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

        $options = [
            $this->generateOptionArray(
                HelperService::PREVIEW_PAGERES,
                'Pageres/PhantomJS (Local)'
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEN_SHOT_API,
                'screenshotapi.io'
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_SCREEN_SHOT_MACHINE,
                'screenshotmachine.com'
            ),
            $this->generateOptionArray(
                HelperService::PREVIEW_DEFAULT,
                'None'
            )
        ];

        if($current === HelperService::PREVIEW_WEBSHOT) {
            $services[] = $this->generateOptionArray(
                HelperService::PREVIEW_WEBSHOT,
                'Passwords Webshot'
            );
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getImagesOptions(): array {
        return [
            $this->generateOptionArray(
                HelperService::IMAGES_IMAGICK,
                'Imagick/GMagick (recommended)',
                ImagickHelper::isAvailable()
            ),
            $this->generateOptionArray(
                HelperService::IMAGES_GDLIB,
                'PHP GDLib'
            )
        ];
    }
}