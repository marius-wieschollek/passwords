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
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\PasswordTagRelation;
use OCA\Passwords\Db\TagMapper;
use OCA\Passwords\Helper\Uuid\UuidHelper;
use OCA\Passwords\Services\Object\PasswordTagRelationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Migration\IOutput;
use Throwable;

/**
 * Class PasswordTagRelationRepair
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class PasswordTagRelationRepair {

    /**
     * @var TagMapper
     */
    protected TagMapper $tagMapper;

    /**
     * @var UuidHelper
     */
    protected UuidHelper $uuidHelper;

    /**
     * @var PasswordMapper
     */
    protected PasswordMapper $passwordMapper;

    /**
     * @var PasswordTagRelationService
     */
    protected PasswordTagRelationService $relationService;

    /**
     * PasswordTagRelationRepair constructor.
     *
     * @param PasswordTagRelationService $relationService
     * @param PasswordMapper             $passwordMapper
     * @param UuidHelper                 $uuidHelper
     * @param TagMapper                  $tagMapper
     */
    public function __construct(PasswordTagRelationService $relationService, PasswordMapper $passwordMapper, UuidHelper $uuidHelper, TagMapper $tagMapper) {
        $this->relationService = $relationService;
        $this->passwordMapper  = $passwordMapper;
        $this->tagMapper       = $tagMapper;
        $this->uuidHelper = $uuidHelper;
    }

    /**
     * @param IOutput $output
     *
     * @throws Exception
     */
    public function run(IOutput $output): void {
        $activeRelations = $this->relationService->findAll();
        /** @var PasswordTagRelation[] $deletedRelations */
        $deletedRelations = $this->relationService->findDeleted();

        $fixed = 0;
        $total = count($activeRelations) + count($deletedRelations);
        $output->info("Checking {$total} password tag relations");
        $output->startProgress($total);
        foreach($activeRelations as $relation) {
            try {
                if($this->repairRelation($relation)) $fixed++;
            } catch(Throwable $e) {
                $output->warning(
                    "Failed to repair relation #{$relation->getId()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }

        foreach($deletedRelations as $relation) {
            try {
                if($this->repairDeletedRelation($relation)) $fixed++;
            } catch(Throwable $e) {
                $output->warning(
                    "Failed to repair relation #{$relation->getId()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
        $output->info("Fixed {$fixed} password tag relations");
    }

    /**
     * @param PasswordTagRelation $relation
     *
     * @return bool
     */
    protected function repairRelation(PasswordTagRelation $relation): bool {
        $fixed = false;

        try {
            $password = $this->passwordMapper->findByUuid($relation->getPassword());
            $tag      = $this->tagMapper->findByUuid($relation->getTag());

            if($password->getUserId() !== $relation->getUserId() || $tag->getUserId() !== $relation->getUserId()) {
                $relation->setDeleted(true);
                $fixed = true;
            }
        } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
            $relation->setDeleted(true);
            $fixed = true;
        }

        if(empty($relation->getUuid())) {
            $relation->setUuid($this->uuidHelper->generateUuid());
            $fixed = true;
        }

        if($fixed) $this->relationService->save($relation);

        return $fixed;
    }

    /**
     * @param PasswordTagRelation $relation
     *
     * @return bool
     */
    protected function repairDeletedRelation(PasswordTagRelation $relation): bool {
        if(empty($relation->getUuid())) {
            $relation->setUuid($this->uuidHelper->generateUuid());
            $this->relationService->save($relation);

            return true;
        }

        return false;
    }
}