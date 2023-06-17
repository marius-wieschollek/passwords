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

namespace OCA\Passwords\Migration\DatabaseRepair;

use Exception;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use OCP\Migration\IOutput;
use Throwable;

class ShareRepair {

    public function __construct(protected ShareService $shareService, protected PasswordService $passwordService) {
    }

    /**
     * @param IOutput $output
     *
     * @throws Exception
     */
    public function run(IOutput $output): void {
        $allModels = $this->shareService->findAll();

        $fixed = 0;
        $total = count($allModels);
        $output->info("Checking {$total} shares");
        $output->startProgress($total);
        foreach($allModels as $model) {
            try {
                if($this->repairModel($model)) $fixed++;
            } catch(Throwable $e) {
                $output->warning(
                    "Failed to repair share #{$model->getUuid()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();
        $output->info("Fixed {$fixed} shares");
    }

    protected function repairModel(Share $share): bool {
        if(!is_null($share->getTargetPassword())) {
            try {
                $this->passwordService->findByUuid($share->getTargetPassword());
            } catch(\Throwable $e) {
                $share->setSourceUpdated(true);
                $share->setTargetPassword(null);
                $this->shareService->save($share);

                return true;
            }
        }

        return false;
    }
}