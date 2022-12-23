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

use OCA\Passwords\Db\ModelInterface;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\Share;
use OCA\Passwords\Db\ShareMapper;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;

/**
 * Class PasswordModelRepair
 *
 * @package OCA\Passwords\Migration\DatabaseRepair
 */
class PasswordModelRepair extends AbstractModelRepair {

    /**
     * @var string
     */
    protected string      $objectName = 'password';
    protected ShareMapper $shareMapper;

    /**
     * PasswordModelRepair constructor.
     *
     * @param PasswordService         $modelService
     * @param PasswordRevisionService $revisionService
     */
    public function __construct(PasswordService $modelService, PasswordRevisionService $revisionService, ShareMapper $shareMapper) {
        parent::__construct($modelService, $revisionService);
        $this->shareMapper = $shareMapper;
    }

    /**
     * @param ModelInterface|Password $model
     *
     * @return bool
     */
    protected function repairModel(ModelInterface $model): bool {

        $fixed = false;
        if(!empty($model->getShareId())) {
            try {
                /** @var Share $share */
                $share = $this->shareMapper->findByUuid($model->getShareId());
                if($share->getTargetPassword() !== $model->getUuid()) {
                    $model->setDeleted(true);
                    $fixed = true;
                }
            } catch(\Throwable $e) {
                $model->setDeleted(true);
                $fixed = true;
            }
        }

        return parent::repairModel($model) || $fixed;
    }
}