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
use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Services\Object\AbstractModelService;
use OCA\Passwords\Services\Object\AbstractRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Migration\IOutput;
use Throwable;

/**
 * Class AbstractModelRepair
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
abstract class AbstractModelRepair {

    /**
     * @var AbstractModelService
     */
    protected AbstractModelService $modelService;

    /**
     * @var AbstractRevisionService
     */
    protected AbstractRevisionService $revisionService;

    /**
     * @var string
     */
    protected string $objectName = 'abstract';

    /**
     * AbstractModelRepair constructor.
     *
     * @param AbstractModelService    $modelService
     * @param AbstractRevisionService $revisionService
     */
    public function __construct(AbstractModelService $modelService, AbstractRevisionService $revisionService) {
        $this->modelService    = $modelService;
        $this->revisionService = $revisionService;
    }

    /**
     * @param IOutput $output
     *
     * @throws Exception
     */
    public function run(IOutput $output): void {
        $allModels = $this->modelService->findAll();

        $fixed = 0;
        $total = count($allModels);
        $output->info("Checking {$total} {$this->objectName} models");
        $output->startProgress($total);
        foreach($allModels as $model) {
            try {
                if($model->getUuid() !== FolderService::BASE_FOLDER_UUID && $this->repairModel($model)) $fixed++;
            } catch(Throwable $e) {
                $output->warning(
                    "Failed to repair model #{$model->getUuid()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
        $output->info("Fixed {$fixed} {$this->objectName} models");
    }

    /**
     * @param ModelInterface $model
     *
     * @return bool
     * @throws Exception
     */
    protected function repairModel(ModelInterface $model): bool {
        $fixed = false;

        $revisions = $this->revisionService->findByModel($model->getUuid());
        if(count($revisions) === 0) {
            $model->setDeleted(true);
            $fixed = true;
        } else {
            $latestRevision = array_pop($revisions);

            try {
                $revision = $this->revisionService->findByUuid($model->getRevision());

                if($revision->getModel() !== $model->getUuid()) {
                    $model->setRevision($latestRevision->getUuid());
                    $fixed = true;
                }
            } catch(DoesNotExistException | MultipleObjectsReturnedException $e) {
                $model->setRevision($latestRevision->getUuid());
                $fixed = true;
            }
        }

        if($fixed) $this->modelService->save($model);

        return $fixed;
    }
}