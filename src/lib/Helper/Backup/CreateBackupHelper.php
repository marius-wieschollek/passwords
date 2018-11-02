<?php

namespace OCA\Passwords\Helper\Backup;

use OCA\Passwords\Db\AbstractMapper;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevisionMapper;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Db\PasswordTagRelationMapper;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Db\TagRevisionMapper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class CreateBackupHelper
 *
 * @package OCA\Passwords\Helper\Backup
 */
class CreateBackupHelper {

    const BACKUP_VERSION = 101;

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
     * @var array
     */
    protected $users = [];

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
     * @return string
     * @throws \Exception
     */
    public function getData(): array {
        $array = [
            'version'              => self::BACKUP_VERSION,
            'passwords'            => $this->getModelArray($this->passwordMapper, $this->passwordRevisionMapper),
            'folders'              => $this->getModelArray($this->folderMapper, $this->folderRevisionMapper),
            'tags'                 => $this->getModelArray($this->tagMapper, $this->tagRevisionMapper),
            'shares'               => $this->getEntityArray($this->shareMapper),
            'passwordTagRelations' => $this->getEntityArray($this->passwordTagRelationMapper),
            'keys'                 => [
                'server' => [
                    'SSEv1ServerKey' => $this->config->getAppValue('SSEv1ServerKey', null)
                ],
                'users'  => $this->getUserKeys()
            ],
            'settings'             => [
                'application' => $this->getApplicationSettings(),
                'users'       => $this->getUserSettings(),
                'clients'     => $this->getClientSettings()
            ]
        ];

        return $array;
    }

    /**
     * @param AbstractMapper $modelMapper
     * @param AbstractMapper $revisionMapper
     *
     * @return array
     * @throws \Exception
     */
    protected function getModelArray(AbstractMapper $modelMapper, AbstractMapper $revisionMapper): array {
        /** @var Password[] $passwords */
        $passwords = $modelMapper->findAll();

        $modelArray = [];
        foreach($passwords as $password) {
            $array              = $password->toArray();
            $array['revisions'] = [];

            $revisions = $revisionMapper->findAllMatching(['model', $password->getUuid()]);
            foreach($revisions as $revision) {
                $array['revisions'][] = $revision->toArray();
            }

            $user = $password->getUserId();
            if(!in_array($user, $this->users)) $this->users[] = $user;
            $modelArray[] = $array;
        }

        return $modelArray;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getUserKeys(): array {
        $keys = [];
        foreach($this->users as $user) {
            $keys[ $user ] = [
                'SSEv1UserKey' => $this->config->getUserValue('SSEv1UserKey', null, $user)
            ];
        }

        return $keys;
    }

    /**
     * @param AbstractMapper $mapper
     *
     * @return array
     */
    protected function getEntityArray(AbstractMapper $mapper): array {
        $entities = $mapper->findAll();

        $entityArray = [];
        foreach($entities as $share) {
            $entityArray[] = $share->toArray();
        }

        return $entityArray;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getUserSettings(): array {
        $settings = [];
        foreach($this->users as $user) {
            $settings[ $user ] = $this->userSettingsHelper->listRaw($user);
        }

        return $settings;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getClientSettings(): array {
        $settings = [];
        foreach($this->users as $user) {
            $settings[ $user ] = $this->config->getUserValue('client/settings', '{}', $user);
        }

        return $settings;
    }

    /**
     * @return array
     */
    protected function getApplicationSettings(): array {
        return [
            'service/security'       => $this->config->getAppValue('service/security', null),
            'service/words'          => $this->config->getAppValue('service/words', null),
            'service/images'         => $this->config->getAppValue('service/images', null),
            'service/favicon'        => $this->config->getAppValue('service/favicon', null),
            'service/preview'        => $this->config->getAppValue('service/preview', null),
            'backup/interval'        => $this->config->getAppValue('backup/interval', null),
            'backup/files/maximum'   => $this->config->getAppValue('backup/files/maximum', null),
            'entity/purge/timeout'   => $this->config->getAppValue('entity/purge/timeout', null),
            'settings/mail/security' => $this->config->getAppValue('settings/mail/security', null),
            'settings/mail/shares'   => $this->config->getAppValue('settings/mail/shares', null),
            'debug/https'            => $this->config->getAppValue('debug/https', null),
        ];
    }
}