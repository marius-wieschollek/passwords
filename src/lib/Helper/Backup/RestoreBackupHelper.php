<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Backup;

use OCA\Passwords\Db\AbstractEntity;
use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\AbstractRevisionMapper;
use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Db\ChallengeMapper;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Db\KeychainMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\PasswordTagRelation;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\AppSettingsService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\UserChallengeService;

/**
 * Class RestoreBackupHelper
 *
 * @package OCA\Passwords\Helper\Backup
 */
class RestoreBackupHelper {

    const BACKUP_VERSION = 105;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var TagMapper
     */
    protected $tagMapper;

    /**
     * @var ShareMapper
     */
    protected $shareMapper;

    /**
     * @var FolderMapper
     */
    protected $folderMapper;

    /**
     * @var PasswordMapper
     */
    protected $passwordMapper;

    /**
     * @var KeychainMapper
     */
    protected $keychainMapper;

    /**
     * @var ChallengeMapper
     */
    protected $challengeMapper;

    /**
     * @var TagRevisionMapper
     */
    protected $tagRevisionMapper;

    /**
     * @var UserSettingsHelper
     */
    protected $userSettingsHelper;

    /**
     * @var AppSettingsService
     */
    protected $appSettingsService;

    /**
     * @var FolderRevisionMapper
     */
    protected $folderRevisionMapper;

    /**
     * @var BackupMigrationHelper
     */
    protected $backupMigrationHelper;

    /**
     * @var PasswordRevisionMapper
     */
    protected $passwordRevisionMapper;

    /**
     * @var PasswordTagRelationMapper
     */
    protected $passwordTagRelationMapper;

    /**
     * CreateBackupHelper constructor.
     *
     * @param TagMapper                 $tagMapper
     * @param ShareMapper               $shareMapper
     * @param FolderMapper              $folderMapper
     * @param ConfigurationService      $config
     * @param PasswordMapper            $passwordMapper
     * @param KeychainMapper            $keychainMapper
     * @param ChallengeMapper           $challengeMapper
     * @param TagRevisionMapper         $tagRevisionMapper
     * @param UserSettingsHelper        $userSettingsHelper
     * @param AppSettingsService        $appSettingsService
     * @param FolderRevisionMapper      $folderRevisionMapper
     * @param BackupMigrationHelper     $backupMigrationHelper
     * @param PasswordRevisionMapper    $passwordRevisionMapper
     * @param PasswordTagRelationMapper $passwordTagRelationMapper
     */
    public function __construct(
        TagMapper $tagMapper,
        ShareMapper $shareMapper,
        FolderMapper $folderMapper,
        ConfigurationService $config,
        PasswordMapper $passwordMapper,
        KeychainMapper $keychainMapper,
        ChallengeMapper $challengeMapper,
        TagRevisionMapper $tagRevisionMapper,
        UserSettingsHelper $userSettingsHelper,
        AppSettingsService $appSettingsService,
        FolderRevisionMapper $folderRevisionMapper,
        BackupMigrationHelper $backupMigrationHelper,
        PasswordRevisionMapper $passwordRevisionMapper,
        PasswordTagRelationMapper $passwordTagRelationMapper
    ) {
        $this->config                    = $config;
        $this->tagMapper                 = $tagMapper;
        $this->shareMapper               = $shareMapper;
        $this->folderMapper              = $folderMapper;
        $this->passwordMapper            = $passwordMapper;
        $this->keychainMapper            = $keychainMapper;
        $this->challengeMapper           = $challengeMapper;
        $this->tagRevisionMapper         = $tagRevisionMapper;
        $this->userSettingsHelper        = $userSettingsHelper;
        $this->appSettingsService        = $appSettingsService;
        $this->folderRevisionMapper      = $folderRevisionMapper;
        $this->backupMigrationHelper     = $backupMigrationHelper;
        $this->passwordRevisionMapper    = $passwordRevisionMapper;
        $this->passwordTagRelationMapper = $passwordTagRelationMapper;
    }

    /**
     * @param array $data
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    public function restore(array $data, array $options): bool {
        if($data['version'] !== self::BACKUP_VERSION) $data = $this->convertData($data);
        $user = $options['user'];

        if($options['data']) {
            $this->deleteData($user);
            $this->restoreKeys($data['keys'], $user);
            $this->restoreData($data, $user);
        }

        if($options['settings']['application']) $this->restoreApplicationSettings($data['settings']['application']);
        if($options['settings']['user']) $this->restoreUserSettings($data['settings']['users'], $user);
        if($options['settings']['client']) $this->restoreClientSettings($data['settings']['clients'], $user);

        return false;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    protected function convertData(array $data): array {
        return $this->backupMigrationHelper->convert($data);
    }

    /**
     * @param null|string $user
     */
    protected function deleteData(?string $user): void {
        $this->deleteEntities($this->tagMapper, $user);
        $this->deleteEntities($this->shareMapper, $user);
        $this->deleteEntities($this->folderMapper, $user);
        $this->deleteEntities($this->passwordMapper, $user);
        $this->deleteEntities($this->keychainMapper, $user);
        $this->deleteEntities($this->challengeMapper, $user);
        $this->deleteEntities($this->tagRevisionMapper, $user);
        $this->deleteEntities($this->folderRevisionMapper, $user);
        $this->deleteEntities($this->passwordRevisionMapper, $user);
        $this->deleteEntities($this->passwordTagRelationMapper, $user);
    }

    /**
     * @param array       $keys
     * @param null|string $user
     *
     * @throws \Exception
     */
    protected function restoreKeys(array $keys, ?string $user): void {
        $this->restoreServerKeys($keys, $user);
        $this->restoreUserKeys($keys, $user);
    }

    /**
     * @param array       $keys
     * @param null|string $user
     *
     * @throws \Exception
     */
    protected function restoreServerKeys(array $keys, ?string $user): void {
        $sseV1ServerKey = $this->config->getAppValue('SSEv1ServerKey', null);
        if($sseV1ServerKey !== $keys['server']['SSEv1ServerKey']) {
            if($user !== null && $sseV1ServerKey !== null) {
                throw new \Exception('Can not restore single user data because server key has changed');
            }

            $this->config->setAppValue('SSEv1ServerKey', $keys['server']['SSEv1ServerKey']);
        }

        $serverSecret = $this->config->getSystemValue('secret', null);
        if($serverSecret !== $keys['server']['secret']) {
            if($user !== null && $serverSecret !== null) {
                throw new \Exception('Can not restore single user data because server secret has changed');
            }

            $this->config->setSystemValue('secret', $keys['server']['secret']);
        }
    }

    /**
     * @param array       $keys
     * @param null|string $user
     *
     * @throws \Exception
     */
    protected function restoreUserKeys(array $keys, ?string $user): void {
        foreach($keys['users'] as $user => $userKeys) {
            if($user !== null && $user !== $user) continue;

            $sseV1UserKey = $this->config->getUserValue('SSEv1UserKey', null, $user);
            if($sseV1UserKey !== $userKeys['SSEv1UserKey']) {
                $this->config->setUserValue('SSEv1UserKey', $userKeys['SSEv1UserKey'], $user);
            }

            if($userKeys['ChallengeId'] !== null) {
                $this->config->setUserValue(UserChallengeService::USER_CHALLENGE_ID, $userKeys['ChallengeId'], $user);
            } else {
                $this->config->deleteUserValue(UserChallengeService::USER_CHALLENGE_ID, $user);
            }
        }
    }

    /**
     * @param array       $data
     * @param null|string $user
     */
    protected function restoreData(array $data, ?string $user): void {
        $this->restoreModels($data['passwords'], $this->passwordMapper, $this->passwordRevisionMapper, Password::class, PasswordRevision::class, $user);
        $this->restoreModels($data['folders'], $this->folderMapper, $this->folderRevisionMapper, Folder::class, FolderRevision::class, $user);
        $this->restoreModels($data['tags'], $this->tagMapper, $this->tagRevisionMapper, Tag::class, TagRevision::class, $user);
        $this->restoreEntities($data['passwordTagRelations'], $this->passwordTagRelationMapper, PasswordTagRelation::class, $user);
        $this->restoreEntities($data['shares'], $this->shareMapper, Share::class, $user);
        $this->restoreEntities($data['keychains'], $this->keychainMapper, Keychain::class, $user);
        $this->restoreEntities($data['challenges'], $this->challengeMapper, Challenge::class, $user);
    }

    /**
     * @param array                  $models
     * @param AbstractMapper         $modelMapper
     * @param AbstractRevisionMapper $revisionMapper
     * @param string                 $modelClass
     * @param string                 $revisionClass
     * @param null|string            $user
     */
    protected function restoreModels(array $models, AbstractMapper $modelMapper, AbstractRevisionMapper $revisionMapper, string $modelClass, string $revisionClass, ?string $user): void {
        foreach($models as $model) {
            if($user !== null && $user !== $model['userId']) continue;
            $revisions = $model['revisions'];
            unset($model['revisions']);
            foreach($revisions as $revision) {
                $this->createAndSaveObject($revision, $revisionMapper, $revisionClass);
            }

            $this->createAndSaveObject($model, $modelMapper, $modelClass);
        }
    }

    /**
     * @param array          $entities
     * @param AbstractMapper $entityMapper
     * @param string         $class
     * @param null|string    $user
     */
    protected function restoreEntities(array $entities, AbstractMapper $entityMapper, string $class, ?string $user): void {
        foreach($entities as $entity) {
            if($user !== null && $user !== $entity['userId']) continue;
            $this->createAndSaveObject($entity, $entityMapper, $class);
        }
    }

    /**
     * @param AbstractMapper $entityMapper
     * @param string         $class
     * @param                $entity
     */
    protected function createAndSaveObject(array $entity, AbstractMapper $entityMapper, string $class): void {
        /** @var AbstractEntity $entityObject */
        $entityObject = new $class();
        foreach($entity as $key => $value) {
            if($key === 'id') continue;
            $entityObject->setProperty($key, $value);
        }
        $entityMapper->insert($entityObject);
    }

    /**
     * @param AbstractMapper $entityMapper
     * @param null|string    $user
     */
    protected function deleteEntities(AbstractMapper $entityMapper, ?string $user): void {
        $entities = array_merge($entityMapper->findAll(), $entityMapper->findAllDeleted());
        foreach($entities as $entity) {
            if($user !== null && $user !== $entity->getUserId()) continue;
            $entityMapper->delete($entity);
        }
    }

    /**
     * @param array       $userSettings
     * @param null|string $user
     *
     * @throws \Exception
     */
    protected function restoreUserSettings(array $userSettings, ?string $user): void {
        foreach($userSettings as $uid => $settings) {
            if($user !== null && $user !== $uid) continue;

            $keys = array_keys($this->userSettingsHelper->listRaw($uid));
            foreach($keys as $key) {
                if(isset($settings[$key]) && $settings[$key] !== null) {
                    $this->userSettingsHelper->set($key, $settings[$key], $uid);
                } else {
                    $this->userSettingsHelper->reset($key, $uid);
                }
            }
        }
    }

    /**
     * @param array       $clientSettings
     * @param null|string $user
     *
     * @throws \Exception
     */
    protected function restoreClientSettings(array $clientSettings, ?string $user): void {
        foreach($clientSettings as $uid => $value) {
            if($user !== null && $user !== $uid) continue;

            $this->config->setUserValue('client/settings', $value, $uid);
        }
    }

    /**
     * @param array $settings
     */
    protected function restoreApplicationSettings(array $settings): void {
        $appSettings = $this->appSettingsService->list();

        foreach($appSettings as $setting) {
            try {
                $name = $setting['name'];
                if(!isset($settings[ $name ]) || $settings[ $name ] === null) {
                    $this->appSettingsService->reset($name);
                } else {
                    $this->appSettingsService->set($name, $settings[ $name ]);
                }
            } catch(ApiException $e) {
            }
        }
    }
}