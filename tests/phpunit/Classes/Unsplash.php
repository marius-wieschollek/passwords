<?php

namespace OCA\Unsplash\Services {
    class SettingsService {
        public function headerbackgroundLink($size): string { return ''; }
    }
}

namespace OCA\Unsplash\ProviderHandler {
    class Provider {
        const SIZE_SMALL = 0;
        const SIZE_NORMAL = 1;
        const SIZE_HIGH = 2;
        const SIZE_ULTRA = 3;
    }
}