<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Backup;

use OCA\Passwords\Helper\Backup\Encryption\BackupSseV1R1Encryption;

/**
 * Class BackupMigrationHelper
 *
 * @package OCA\Passwords\Helper\Backup
 */
class BackupMigrationHelper {

    /**
     * @var BackupSseV1R1Encryption
     */
    protected $encryption;

    /**
     * BackupMigrationHelper constructor.
     *
     * @param BackupSseV1R1Encryption $encryption
     */
    public function __construct(BackupSseV1R1Encryption $encryption) {
        $this->encryption = $encryption;
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

        if($version < 101) $data = $this->upgrade100($data);
        if($version < 102) $data = $this->upgrade101($data);
        if($version < 103) $data = $this->upgrade102($data);

        $data['version'] = RestoreBackupHelper::BACKUP_VERSION;

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function upgrade100(array $data): array {
        $data['passwordTagRelations'] = $data['password_tag_relations'];
        unset($data['password_tag_relations']);

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function upgrade101(array $data): array {
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
     * @param array $database
     *
     * @return array
     * @throws \OCP\PreConditionNotMetException
     */
    protected function upgrade102(array $database): array {
        $this->encryption->setKeys($database['keys']);

        foreach($database['passwords'] as &$object) {
            foreach($object['revisions'] as $key => $revision) {
                $revision = $this->encryption->decryptArray($revision);

                if($revision['customFields'] === '{}') {
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