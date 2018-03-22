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
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\TagService;

/**
 * Class DeleteUserDataHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class DeleteUserDataHelper {

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
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var PasswordService
     */
    protected $passwordService;

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
     * @param UserSettingsHelper   $settings
     * @param FolderService        $folderService
     * @param ConfigurationService $config
     * @param PasswordService      $passwordService
     */
    public function __construct(
        TagService $tagService,
        UserSettingsHelper $settings,
        FolderService $folderService,
        ConfigurationService $config,
        PasswordService $passwordService
    ) {
        $this->tagService      = $tagService;
        $this->settings        = $settings;
        $this->folderService   = $folderService;
        $this->config          = $config;
        $this->passwordService = $passwordService;
    }

    /**
     * @param string $userId
     *
     * @throws \Exception
     */
    public function deleteUserData(?string $userId = null): void {
        if($userId !== null) {
            $this->deleteObjectsByUserId($this->tagService, $userId);
            $this->deleteObjectsByUserId($this->folderService, $userId);
            $this->deleteObjectsByUserId($this->passwordService, $userId);
        } else {
            $this->deleteObjects($this->tagService);
            $this->deleteObjects($this->folderService);
            $this->deleteObjects($this->passwordService);
        }
        $this->deleteUserSettings($userId);
        $this->deleteUserConfig($userId);
    }

    /**
     * @param AbstractModelService $service
     *
     * @throws \Exception
     */
    protected function deleteObjects(AbstractModelService $service): void {
        /** @var EntityInterface $tags */
        $tags = $service->findAll();

        foreach($tags as $tag) {
            $service->delete($tag);
        }
    }

    /**
     * @param AbstractModelService $service
     * @param string               $userId
     *
     * @throws \Exception
     */
    protected function deleteObjectsByUserId(AbstractModelService $service, string $userId): void {
        /** @var EntityInterface $tags */
        $tags = $service->findByUserId($userId);

        foreach($tags as $tag) {
            $service->delete($tag);
        }
    }

    /**
     * @param string $userId
     */
    protected function deleteUserSettings(?string $userId = null): void {
        $settings = array_keys($this->settings->list($userId));

        foreach($settings as $setting) {
            $this->settings->reset($setting, $userId);
        }
    }

    /**
     * @param string $userId
     */
    protected function deleteUserConfig(?string $userId = null): void {
        foreach($this->userConfigKeys as $key) {
            $this->config->deleteUserValue($key, $userId);
        }
    }
}