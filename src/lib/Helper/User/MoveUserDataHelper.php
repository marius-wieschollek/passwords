<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\User;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\SessionMapper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractService;
use OCA\Passwords\Services\Object\ChallengeService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCA\Passwords\Services\Object\ShareService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;

/**
 * Class MoveUserDataHelper
 *
 * @package OCA\Passwords\Helper\User
 */
class MoveUserDataHelper {

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
     * @var KeychainService
     */
    protected $keychainService;

    /**
     * @var ChallengeService
     */
    protected $challengeService;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * @var FolderRevisionService
     */
    protected $folderRevisionService;

    /**
     * @var PasswordRevisionService
     */
    protected $passwordRevisionService;

    /**
     * @var PasswordTagRelationService
     */
    protected $passwordTagRelationService;

    /**
     * @var SessionMapper
     */
    protected $sessionMapper;

    /**
     * @var array
     */
    protected $moveConfigKeys
        = [
            'SSEv1UserKey',
            'client/settings',
            'user/challenge/id'
        ];

    /**
     * DeleteUserDataHelper constructor.
     *
     * @param TagService                 $tagService
     * @param ShareService               $shareService
     * @param SessionMapper              $sessionMapper
     * @param UserSettingsHelper         $settings
     * @param FolderService              $folderService
     * @param ConfigurationService       $config
     * @param EnvironmentService         $environment
     * @param PasswordService            $passwordService
     * @param KeychainService            $keychainService
     * @param ChallengeService           $challengeService
     * @param TagRevisionService         $tagRevisionService
     * @param FolderRevisionService      $folderRevisionService
     * @param PasswordRevisionService    $passwordRevisionService
     * @param PasswordTagRelationService $passwordTagRelationService
     */
    public function __construct(
        TagService $tagService,
        ShareService $shareService,
        SessionMapper $sessionMapper,
        UserSettingsHelper $settings,
        FolderService $folderService,
        ConfigurationService $config,
        EnvironmentService $environment,
        PasswordService $passwordService,
        KeychainService $keychainService,
        ChallengeService $challengeService,
        TagRevisionService $tagRevisionService,
        FolderRevisionService $folderRevisionService,
        PasswordRevisionService $passwordRevisionService,
        PasswordTagRelationService $passwordTagRelationService
    ) {
        $this->userId                     = $environment->getUserId();
        $this->config                     = $config;
        $this->settings                   = $settings;
        $this->tagService                 = $tagService;
        $this->shareService               = $shareService;
        $this->folderService              = $folderService;
        $this->passwordService            = $passwordService;
        $this->keychainService            = $keychainService;
        $this->challengeService           = $challengeService;
        $this->tagRevisionService         = $tagRevisionService;
        $this->folderRevisionService      = $folderRevisionService;
        $this->passwordRevisionService    = $passwordRevisionService;
        $this->passwordTagRelationService = $passwordTagRelationService;
        $this->sessionMapper = $sessionMapper;
    }

    /**
     * @param string $sourceUser
     * @param string $targetUser
     *
     * @throws \Exception
     */
    public function moveUserData(string $sourceUser, string $targetUser): void {
        if($targetUser === $sourceUser) throw new \Exception('Target and source user match');
        if($this->userId !== null && $this->userId != $sourceUser) throw new \Exception('Invalid user id '.$sourceUser);

        $this->closeSessions($sourceUser);
        $this->moveObjects($this->tagService, $sourceUser, $targetUser);
        $this->moveObjects($this->folderService, $sourceUser, $targetUser);
        $this->moveObjects($this->passwordService, $sourceUser, $targetUser);
        $this->moveObjects($this->keychainService, $sourceUser, $targetUser);
        $this->moveObjects($this->challengeService, $sourceUser, $targetUser);
        $this->moveObjects($this->tagRevisionService, $sourceUser, $targetUser);
        $this->moveObjects($this->folderRevisionService, $sourceUser, $targetUser);
        $this->moveObjects($this->passwordRevisionService, $sourceUser, $targetUser);
        $this->moveObjects($this->passwordTagRelationService, $sourceUser, $targetUser);
        $this->moveShares($sourceUser, $targetUser);
        $this->moveUserConfig($sourceUser, $targetUser);
        $this->moveUserSettings($sourceUser, $targetUser);
    }

    /**
     * @param AbstractModelService|AbstractService $service
     * @param string                               $sourceUser
     * @param string                               $targetUser
     *
     * @throws \Exception
     */
    protected function moveObjects(AbstractService $service, string $sourceUser, string $targetUser): void {
        /** @var EntityInterface[] $objects */
        $objects = $service->findByUserId($sourceUser);

        foreach($objects as $object) {
            $object->setUserId($targetUser);
            $service->save($object);
        }
    }

    /**
     * @param string $sourceUser
     * @param string $targetUser
     *
     * @throws \Exception
     */
    protected function moveShares(string $sourceUser, string $targetUser): void {
        $objects = $this->shareService->findWithUserId($sourceUser);

        foreach($objects as $object) {
            if($object->getUserId() === $sourceUser) $object->setUserId($targetUser);
            if($object->getReceiver() === $sourceUser) $object->setReceiver($targetUser);
            $this->shareService->save($object);
        }
    }

    /**
     * @param string $userId
     *
     * @throws \Exception
     */
    protected function closeSessions(string $userId): void {
        $sessions = $this->sessionMapper->findAllByUserId($userId);

        foreach($sessions as $session) {
            $this->sessionMapper->delete($session);
        }
    }

    /**
     * @param string $sourceUser
     * @param string $targetUser
     *
     * @throws \Exception
     */
    protected function moveUserSettings(string $sourceUser, string $targetUser): void {
        $settings = $this->settings->listRaw($sourceUser);

        foreach($settings as $key => $value) {
            if($value !== null) {
                $this->settings->set($key, $value, $targetUser);
                $this->settings->reset($key, $sourceUser);
            }
        }
    }

    /**
     * @param string $sourceUser
     * @param string $targetUser
     *
     * @throws \Exception
     */
    protected function moveUserConfig(string $sourceUser, string $targetUser): void {
        foreach($this->moveConfigKeys as $key) {
            if($this->config->hasUserValue($key, $sourceUser)) {
                $value = $this->config->getUserValue($key, null, $sourceUser);
                $this->config->setUserValue($key, $value, $targetUser);
                $this->config->deleteUserValue($key, $sourceUser);
            }
        }
    }
}