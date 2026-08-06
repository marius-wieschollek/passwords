<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Services\ConfigurationService;
use OCP\Share\IManager;

/**
 * Class ShareSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ShareSettingsHelper {

    /**
     * @var null|string
     */
    protected ?string $userId;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var IManager
     */
    protected IManager $shareManager;

    /**
     * ShareSettingsHelper constructor.
     *
     * @param null|string          $userId
     * @param IManager             $shareManager
     * @param ConfigurationService $config
     */
    public function __construct(?string $userId, IManager $shareManager, ConfigurationService $config) {
        $this->config       = $config;
        $this->userId       = $userId;
        $this->shareManager = $shareManager;
    }

    /**
     * @param $key
     *
     * @return bool|string[]|null
     */
    public function get($key) {
        switch($key) {
            case 'enabled':
                return $this->isSharingEnabled();
            case 'groups.enabled':
                return $this->isGroupSharingEnabled();
            case 'resharing':
                return $this->isReSharingEnabled();
            case 'autocomplete':
                return $this->isSharingEnumerationEnabled();
            case 'types':
                return $this->isGroupSharingEnabled() ? ['user', 'group']:['user'];
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        return [
            'server.sharing.enabled'        => $this->isSharingEnabled(),
            'server.sharing.groups.enabled' => $this->isGroupSharingEnabled(),
            'server.sharing.resharing'      => $this->isReSharingEnabled(),
            'server.sharing.autocomplete'   => $this->isSharingEnumerationEnabled(),
            'server.sharing.types'          => $this->get('types')
        ];
    }

    /**
     * @return bool
     */
    protected function isSharingEnabled(): bool {
        if($this->userId === null) {
            return $this->shareManager->shareApiEnabled();
        }

        return $this->shareManager->shareApiEnabled() && !$this->shareManager->sharingDisabledForUser($this->userId);
    }

    /**
     * @return bool
     */
    protected function isGroupSharingEnabled(): bool {
        return $this->shareManager->allowGroupSharing();
    }

    /**
     * @return bool
     */
    protected function isReSharingEnabled(): bool {
        return $this->config->getAppValue('shareapi_allow_resharing', 'yes', 'core') === 'yes';
    }

    /**
     * @return bool
     */
    protected function isSharingEnumerationEnabled(): bool {
        return $this->shareManager->allowEnumeration();
    }
}