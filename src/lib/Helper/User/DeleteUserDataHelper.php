<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagService;

/**
 * Class DeleteUserDataHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class DeleteUserDataHelper {

    /**
     * @var null|string
     */
    protected $userId;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var UserSettingsHelper
     */
    protected $settings;

    /**
     * @var FolderService
     */
    protected $folderService;

    /**
     * @var PasswordService
     */
    protected $passwordService;

    /**
     * @var ShareService
     */
    protected $shareService;

    /**
     * @var array
     */
    protected $userConfigKeys
        = [
            'SSEv1UserKey',
            'client/settings',
            'webui_token',
            'webui_token_id'
        ];

    /**
     * DeleteUserDataHelper constructor.
     *
     * @param TagService           $tagService
     * @param ShareService         $shareService
     * @param UserSettingsHelper   $settings
     * @param FolderService        $folderService
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     * @param PasswordService      $passwordService
     */
    public function __construct(
        TagService $tagService,
        ShareService $shareService,
        UserSettingsHelper $settings,
        FolderService $folderService,
        ConfigurationService $config,
        EnvironmentService $environment,
        PasswordService $passwordService
    ) {
        $this->userId          = $environment->getUserId();
        $this->config          = $config;
        $this->settings        = $settings;
        $this->tagService      = $tagService;
        $this->shareService    = $shareService;
        $this->folderService   = $folderService;
        $this->passwordService = $passwordService;
    }

    /**
     * @param string $userId
     *
     * @throws \Exception
     */
    public function deleteUserData(string $userId): void {
        if($this->userId !== null && $this->userId != $userId) throw new \Exception('Invalid user id '.$userId);

        $this->deleteObjects($this->tagService, $userId);
        $this->deleteObjects($this->folderService, $userId);
        $this->deleteObjects($this->passwordService, $userId);
        $this->deleteObjects($this->shareService, $userId);
        $this->deleteUserSettings($userId);
        $this->deleteUserConfig($userId);
    }

    /**
     * @param AbstractModelService|ShareService|AbstractService $service
     * @param string                                            $userId
     *
     * @throws \Exception
     */
    protected function deleteObjects(AbstractService $service, string $userId): void {
        /** @var EntityInterface $objects */
        $objects = $service->findByUserId($userId);

        foreach($objects as $object) {
            $service->delete($object);
        }
    }

    /**
     * @param string $userId
     *
     * @throws \Exception
     */
    protected function deleteUserSettings(string $userId): void {
        $settings = array_keys($this->settings->list($userId));

        foreach($settings as $setting) {
            $this->settings->reset($setting, $userId);
        }
    }

    /**
     * @param string $userId
     *
     * @throws \Exception
     */
    protected function deleteUserConfig(string $userId): void {
        foreach($this->userConfigKeys as $key) {
            $this->config->deleteUserValue($key, $userId);
        }
    }
}