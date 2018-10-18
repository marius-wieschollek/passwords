<?php

namespace OCA\Passwords\Helper\Backup;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\AbstractModelEntity;
use OCA\Passwords\Db\AbstractRevisionEntity;
use OCA\Passwords\Db\AbstractRevisionMapper;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class RestoreBackupHelper
 *
 * @package OCA\Passwords\Helper\Backup
 */
class RestoreBackupHelper {

    const BACKUP_VERSION = 100;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var TagMapper
     */
    protected $tagMapper;

    /**
     * @var FolderMapper
     */
    protected $folderMapper;

    /**
     * @var ShareMapper
     */
    protected $shareMapper;

    /**
     * @var PasswordMapper
     */
    protected $passwordMapper;

    /**
     * @var TagRevisionMapper
     */
    protected $tagRevisionMapper;

    /**
     * @var UserSettingsHelper
     */
    protected $userSettingsHelper;

    /**
     * @var FolderRevisionMapper
     */
    protected $folderRevisionMapper;

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
     * @param TagRevisionMapper         $tagRevisionMapper
     * @param UserSettingsHelper        $userSettingsHelper
     * @param FolderRevisionMapper      $folderRevisionMapper
     * @param PasswordRevisionMapper    $passwordRevisionMapper
     * @param PasswordTagRelationMapper $passwordTagRelationMapper
     */
    public function __construct(
        TagMapper $tagMapper,
        ShareMapper $shareMapper,
        FolderMapper $folderMapper,
        ConfigurationService $config,
        PasswordMapper $passwordMapper,
        TagRevisionMapper $tagRevisionMapper,
        UserSettingsHelper $userSettingsHelper,
        FolderRevisionMapper $folderRevisionMapper,
        PasswordRevisionMapper $passwordRevisionMapper,
        PasswordTagRelationMapper $passwordTagRelationMapper
    ) {
        $this->config                    = $config;
        $this->tagMapper                 = $tagMapper;
        $this->shareMapper               = $shareMapper;
        $this->folderMapper              = $folderMapper;
        $this->passwordMapper            = $passwordMapper;
        $this->tagRevisionMapper         = $tagRevisionMapper;
        $this->userSettingsHelper        = $userSettingsHelper;
        $this->folderRevisionMapper      = $folderRevisionMapper;
        $this->passwordRevisionMapper    = $passwordRevisionMapper;
        $this->passwordTagRelationMapper = $passwordTagRelationMapper;
    }

    /**
     * @param array $data
     * @param array $options
     *
     * @return bool
     */
    public function restore(array $data, array $options): bool {
        if($data['version'] !== self::BACKUP_VERSION) $this->convertData($data);

        if($options['data']) {
            $this->restoreKeys($data, $options);
            $this->restoreData($data, $options);
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function convertData(array $data): array {

        return $data;
    }

    /**
     * @param array $data
     * @param array $options
     *
     * @throws \Exception
     */
    protected function restoreKeys(array $data, array $options) {
        $keys = $data['keys'];

        $sseKeyChanged  = false;
        $sseV1ServerKey = $this->config->getAppValue('SSEv1ServerKey', null);
        if($sseV1ServerKey !== $keys['server']['SSEv1ServerKey']) {
            if($options['user'] !== null && $sseV1ServerKey !== null) {
                throw new \Exception('Can not restore single user data because server key has changed');
            }

            $this->config->setAppValue('SSEv1ServerKey', $keys['server']['SSEv1ServerKey']);
            if($options['user'] === null) {
                $this->deleteAllWithSseV1();
            } else {
                $this->deleteAllByUserWithSseV1($options['user']);
            }
            $sseKeyChanged = true;
        }

        foreach($keys['users'] as $user => $userKeys) {
            if($options['user'] !== null && $options['user'] !== $user) continue;

            $sseV1UserKey = $this->config->getUserValue('SSEv1UserKey', null, $user);
            if($sseV1UserKey !== $userKeys['SSEv1UserKey']) {
                $this->config->setUserValue('SSEv1UserKey', $userKeys['SSEv1UserKey'], $user);

                if(!$sseKeyChanged) $this->deleteAllByUserWithSseV1($options['user']);
            }
        }
    }

    /**
     * @param array $data
     * @param array $options
     *
     * @throws MultipleObjectsReturnedException
     */
    protected function restoreData(array $data, array $options) {
        $this->restoreModels($data['passwords'], $this->passwordMapper, $this->passwordRevisionMapper, Password::class, PasswordRevision::class);
        $this->restoreModels($data['folders'], $this->folderMapper, $this->folderRevisionMapper, Folder::class, FolderRevision::class);
        $this->restoreModels($data['tags'], $this->tagMapper, $this->tagRevisionMapper, Tag::class, TagRevision::class);
    }

    /**
     * @param array                  $models
     * @param AbstractMapper         $modelMapper
     * @param AbstractRevisionMapper $revisionMapper
     * @param string                 $modelClass
     * @param string                 $revisionClass
     *
     * @throws MultipleObjectsReturnedException
     */
    protected function restoreModels(array $models, AbstractMapper $modelMapper, AbstractRevisionMapper $revisionMapper, string $modelClass, string $revisionClass) {
        foreach($models as $model) {

            try {
                $object = $modelMapper->findByUuid($model['uuid']);
                $modelMapper->delete($object);
                $oldRevisions = $revisionMapper->findAllMatching(['model', $model['uuid']]);
                foreach($oldRevisions as $oldRevision) $revisionMapper->delete($oldRevision);
            } catch(DoesNotExistException $e) {
            }

            $revisions = $model['revisions'];
            unset($model['revisions']);
            foreach($revisions as $revision) {
                /** @var AbstractRevisionEntity $revisionObject */
                $revisionObject = new $revisionClass();
                foreach($revision as $key => $value) {
                    if($key === 'id') continue;
                    $revisionObject->setProperty($key, $value);
                    $revisionMapper->insert($revisionObject);
                }
            }

            /** @var AbstractModelEntity $modelObject */
            $modelObject = new $modelClass();
            foreach($model as $key => $value) {
                if($key === 'id') continue;
                $modelObject->setProperty($key, $value);
                $modelMapper->insert($modelObject);
            }
        }
    }
}