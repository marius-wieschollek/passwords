<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\UserMigration;

use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\UserMigration\Export\RevisionedItemsExporter;
use OCA\Passwords\UserMigration\Export\SettingsExporter;
use OCA\Passwords\UserMigration\Export\SimpleItemExporter;
use OCA\Passwords\UserMigration\Import\RevisionedItemsImporter;
use OCA\Passwords\UserMigration\Import\SettingsImporter;
use OCA\Passwords\UserMigration\Import\SimpleItemImporter;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class PasswordsMigrator implements IMigrator, ISizeEstimationMigrator {

    use TMigratorBasicVersionHandling;

    const string DATA_FILE = 'passwords.json';

    /**
     * @param KeychainService         $keychainService
     * @param SettingsExporter        $settingsExporter
     * @param SettingsImporter        $settingsImporter
     * @param EnvironmentService      $environmentService
     * @param SimpleItemExporter      $simpleItemExporter
     * @param SimpleItemImporter      $simpleItemImporter
     * @param NotificationService     $notificationService
     * @param RevisionedItemsExporter $revisionedItemsExporter
     * @param RevisionedItemsImporter $revisionedItemsImporter
     * @param PasswordRevisionService $passwordRevisionService
     * @param IL10N                   $l10n
     */
    public function __construct(
        protected KeychainService         $keychainService,
        protected SettingsExporter        $settingsExporter,
        protected SettingsImporter        $settingsImporter,
        protected EnvironmentService      $environmentService,
        protected SimpleItemExporter      $simpleItemExporter,
        protected SimpleItemImporter      $simpleItemImporter,
        protected NotificationService     $notificationService,
        protected RevisionedItemsExporter $revisionedItemsExporter,
        protected RevisionedItemsImporter $revisionedItemsImporter,
        protected PasswordRevisionService $passwordRevisionService,
        protected IL10N                   $l10n
    ) {
        $this->mandatory = true;
    }

    public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
        try {
            if(!empty($this->keychainService->findAll())) {
                if($this->environmentService->getRunType() !== EnvironmentService::TYPE_CLI) {
                    $this->notificationService->sendUserExportNotPossibleNotification($user->getUID(), 'cse');
                }
                $output->writeln('Passwords app export not possible due to E2EE');

                return;
            }

            $output->writeln('Exporting passwords app data to '.self::DATA_FILE.'…');

            $data = [
                ...$this->revisionedItemsExporter->exportData($user->getUID()),
                ...$this->simpleItemExporter->exportData($user->getUID()),
                ...$this->settingsExporter->exportData($user->getUID()),
            ];
            $exportDestination->addFileContents(self::DATA_FILE, json_encode($data));
        } catch(Throwable $e) {
            echo $e->getMessage();
            throw new UserMigrationException('Could not export passwords data: '.$e->getMessage(), 0, $e);
        }
    }

    public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
        try {
            if($importSource->getMigratorVersion($this->getId()) === null || !$importSource->pathExists(self::DATA_FILE)) {
                $output->writeln('No version for passwords, skipping import…');

                return;
            }

            $output->writeln('Importing passwords app data from '.self::DATA_FILE.'…');
            $data = json_decode($importSource->getFileContents(self::DATA_FILE), true);

            $this->settingsImporter->importData($user->getUID(), $data);
            $this->simpleItemImporter->importData($user->getUID(), $data);
            $this->revisionedItemsImporter->importData($user->getUID(), $data);
        } catch(Throwable $e) {
            echo $e->getMessage();
            throw new UserMigrationException('Could not import passwords data: '.$e->getMessage(), 0, $e);
        }
    }

    public function getId(): string {
        return 'passwords';
    }

    public function getDisplayName(): string {
        return $this->l10n->t('Passwords');
    }

    public function getDescription(): string {
        return $this->l10n->t('Decrypted passwords database with passwords, folders, tags and settings. (Does not include passwords shared with you. Does not work with E2E enabled)');
    }

    public function getEstimatedExportSize(IUser $user): int {
        return $this->passwordRevisionService->count() * 4;
    }
}