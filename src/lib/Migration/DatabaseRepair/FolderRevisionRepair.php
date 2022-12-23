<?php
/*
 * @copyright 2020 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Migration\DatabaseRepair;

use Exception;
use OCA\Passwords\Db\FolderMapper;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

/**
 * Class FolderRevisionRepairHelper
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class FolderRevisionRepair extends AbstractRevisionRepair {

    /**
     * @var string
     */
    protected string $objectName = 'folder';

    /**
     * FolderRevisionRepair constructor.
     *
     * @param FolderMapper          $modelMapper
     * @param FolderRevisionService $revisionService
     * @param EncryptionService     $encryption
     * @param EnvironmentService    $environment
     */
    public function __construct(FolderMapper $modelMapper, FolderRevisionService $revisionService, EncryptionService $encryption, EnvironmentService $environment) {
        parent::__construct($modelMapper, $revisionService, $encryption, $environment);
    }

    /**
     * @param FolderRevision|RevisionInterface $revision
     *
     * @return bool
     * @throws Exception
     */
    public function repairRevision(RevisionInterface $revision): bool {
        $fixed = false;

        // Check if the folder is its own parent
        if($revision->getParent() !== FolderService::BASE_FOLDER_UUID && $revision->getParent() === $revision->getModel()) {
            $revision->setParent(FolderService::BASE_FOLDER_UUID);
            $fixed = true;
        }

        // Check if the parent exists at all
        if($revision->getParent() !== FolderService::BASE_FOLDER_UUID) {
            try {
                $this->modelMapper->findByUuid($revision->getParent());
            } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
                $revision->setParent(FolderService::BASE_FOLDER_UUID);
                $fixed = true;
            }
        }

        // Check for any recursion in the root structure
        if($revision->getParent() !== FolderService::BASE_FOLDER_UUID && !$this->checkPathEndsAtRoot($revision)) {
            $revision->setParent(FolderService::BASE_FOLDER_UUID);
            $fixed = true;
        }

        if($fixed) $this->revisionService->save($revision);

        return parent::repairRevision($revision) || $fixed;
    }

    /**
     * Check the path of each folder for a root loop
     *
     * @param FolderRevision $revision
     *
     * @return bool
     */
    protected function checkPathEndsAtRoot(FolderRevision $revision): bool {
        $modelIds        = [];
        $currentRevision = $revision;

        while(true) {
            try {
                /** @var FolderRevision $currentRevision */
                $currentRevision = $this->revisionService->findCurrentRevisionByModel($currentRevision->getParent());

                if($currentRevision->getParent() === FolderService::BASE_FOLDER_UUID) {
                    return true;
                }

                if(in_array($currentRevision->getModel(), $modelIds)) {
                    return false;
                }

                $modelIds[] = $currentRevision->getModel();
            } catch(\Throwable $e) {
                return false;
            }
        }
    }
}