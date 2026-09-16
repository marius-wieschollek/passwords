<?php

namespace OCA\Passwords\Integrations;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Unsplash\ProviderHandler\Provider;
use OCA\Unsplash\Services\SettingsService;

class UnsplashIntegration {

    public function __construct(protected ConfigurationService $config) {
    }

    public function isAvailable(): bool {
        return $this->config->isAppEnabled('unsplash') && class_exists(\OCA\Unsplash\Services\SettingsService::class);
    }

    public function getBackgroundImage(): ?string {
        try {
            $settings = \OC::$server->get(SettingsService::class);

            return $settings->headerbackgroundLink(Provider::SIZE_NORMAL);
        } catch (\Throwable $e) {
            return null;
        }
    }
}