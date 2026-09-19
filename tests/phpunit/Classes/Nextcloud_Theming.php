<?php

namespace OCA\Theming\AppInfo {

    class Application {
        public const string APP_ID = 'theming';
    }
}

namespace OCA\Theming\Service {

    class BackgroundService {
        public const DEFAULT_BACKGROUND_IMAGE = 'jo-myoung-hee-fluid.webp';
    }
}

namespace OCA\Theming {

    class ThemingDefaults {
        public function getLogo($useSvg = true): string {
            return '';
        }

        public function getName(): string {
            return '';
        }

        public function getTextColorPrimary(): string {
            return '';
        }

        public function getColorPrimary(): string {
            return '';
        }

        public function getColorBackground(): string {
            return '';
        }

        public function getBackground(): string {
            return '';
        }
    }

    class Capabilities {
        public function getCapabilities(): array {
            return [];
        }
    }
}