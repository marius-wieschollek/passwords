<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Backup;

use OCA\Passwords\Encryption\Backup\SseV1BackupEncryption;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\ConfigurationService;

/**
 * Class BackupMigrationHelper
 *
 * @package OCA\Passwords\Helper\Backup
 */
class BackupMigrationHelper {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var UuidHelper
     */
    protected $uuidHelper;

    /**
     * @var SseV1BackupEncryption
     */
    protected $encryption;

    /**
     * BackupMigrationHelper constructor.
     *
     * @param UuidHelper            $uuidHelper
     * @param SseV1BackupEncryption $encryption
     * @param ConfigurationService  $config
     */
    public function __construct(UuidHelper $uuidHelper, SseV1BackupEncryption $encryption, ConfigurationService $config) {
        $this->config     = $config;
        $this->encryption = $encryption;
        $this->uuidHelper = $uuidHelper;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function convert(array $data): array {
        $version = $data['version'];

        $this->validateBackup($version);

        if($version < 101) $data = $this->to101($data);
        if($version < 102) $data = $this->to102($data);
        if($version < 103) $data = $this->to103($data);
        if($version < 104) $data = $this->to104($data);
        if($version < 105) $data = $this->to105($data);

        $data['version'] = RestoreBackupHelper::BACKUP_VERSION;

        return $data;
    }

    /**
     * Rename password <-> tag relation section
     *
     * @param array $data
     *
     * @return array
     */
    protected function to101(array $data): array {
        $data['passwordTagRelations'] = $data['password_tag_relations'];
        unset($data['password_tag_relations']);

        return $data;
    }

    /**
     * Remove legacy fields
     *
     * @param array $data
     *
     * @return array
     */
    protected function to102(array $data): array {
        foreach(['passwords', 'folders', 'tags'] as $type) {
            foreach($data[ $type ] as &$object) {
                foreach($object['revisions'] as &$revision) {
                    if(isset($revision['client'])) unset($revision['client']);
                    if(isset($revision['favourite'])) unset($revision['favourite']);
                }
            }
        }

        return $data;
    }

    /**
     * Convert old custom fields data structure
     *
     * @param array $database
     *
     * @return array
     * @throws \Exception
     */
    protected function to103(array $database): array {
        $this->encryption->setKeys($database['keys']);

        foreach($database['passwords'] as &$object) {
            foreach($object['revisions'] as $key => $revision) {
                $revision = $this->encryption->decryptArray($revision);

                if($revision['customFields'] === '{}' || empty($revision['customFields'])) {
                    $revision['customFields'] = '[]';
                } else {
                    $oldFields = json_decode($revision['customFields'], true);
                    $newFields = [];
                    foreach($oldFields as $label => $data) {
                        if(substr($label, 0, 1) === '_') $data['type'] = 'data';

                        $newFields[] = ['label' => $label, 'type' => $data['type'], 'value' => $data['value']];
                    }
                    $revision['customFields'] = json_encode($newFields);
                }

                $object['revisions'][ $key ] = $this->encryption->encryptArray($revision);
            }
        }

        return $database;
    }

    /**
     * Remove messy values from custom fields
     *
     * @param array $database
     *
     * @return array
     * @throws \Exception
     */
    protected function to104(array $database): array {
        $this->encryption->setKeys($database['keys']);

        foreach($database['passwords'] as &$object) {
            foreach($object['revisions'] as $key => $revision) {
                $revision = $this->encryption->decryptArray($revision);
                if($revision['customFields'] === '[]') continue;

                $oldFields = json_decode($revision['customFields'], true);
                $newFields = [];
                foreach($oldFields as $label => $data) {
                    $newFields[] = ['label' => $data['label'], 'type' => $data['type'], 'value' => $data['value']];
                }
                $revision['customFields'] = json_encode($newFields);

                $object['revisions'][ $key ] = $this->encryption->encryptArray($revision);
            }
        }

        return $database;
    }

    /**
     * Add required cse values and convert app settings
     *
     * @param array $data
     *
     * @return array
     */
    protected function to105(array $data): array {
        foreach(['passwords', 'folders', 'tags'] as $type) {
            foreach($data[ $type ] as &$object) {
                foreach($object['revisions'] as &$revision) {
                    $revision['cseKey'] = '';
                }
            }
        }

        foreach($data['passwordTagRelations'] as &$object) {
            $object['uuid'] = $this->uuidHelper->generateUuid();
        }

        $data['keychains']                = [];
        $data['challenges']                = [];
        $data['keys']['server']['secret'] = $this->config->getSystemValue('secret');
        foreach($data['keys']['users'] as $user => $keys) {
            $data['keys']['users'][ $user ]['ChallengeId'] = null;
        }

        $oldSettings                  = $data['settings']['application'];
        $data['settings']['application'] = [
            'backup.interval'        => $oldSettings['backup/interval'],
            'backup.files.max'       => $oldSettings['backup/files/maximum'],
            'service.words'          => $oldSettings['service/words'],
            'service.images'         => $oldSettings['service/images'],
            'service.favicon'        => $oldSettings['service/favicon'],
            'service.preview'        => $oldSettings['service/preview'],
            'service.security'       => $oldSettings['service/security'],
            'entity.purge.timeout'   => $oldSettings['entity/purge/timeout'],
            'settings.mail.shares'   => $oldSettings['settings/mail/shares'],
            'settings.mail.security' => $oldSettings['settings/mail/security']
        ];

        return $data;
    }

    /**
     * @param $version
     *
     * @throws \Exception
     */
    protected function validateBackup($version): void {
        if($version < 100) {
            throw new \Exception('This seems to be a client backup. It can only be restored using the web interface');
        }

        if($version > RestoreBackupHelper::BACKUP_VERSION) {
            throw new \Exception('Unsupported backup version: '.$version);
        }
    }
}