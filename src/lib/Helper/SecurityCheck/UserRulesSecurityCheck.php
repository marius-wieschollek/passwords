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

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\PasswordSecurityCheckService;

/**
 * Class UserRulesSecurityCheck
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class UserRulesSecurityCheck {

    /**
     * @var PasswordRevisionMapper
     */
    protected PasswordRevisionMapper $revisionMapper;

    /**
     * @var UserSettingsHelper
     */
    protected UserSettingsHelper $userSettingsHelper;

    /**
     * UserRulesSecurityCheck constructor.
     *
     * @param PasswordRevisionMapper $revisionMapper
     * @param UserSettingsHelper     $userSettingsHelper
     */
    public function __construct(PasswordRevisionMapper $revisionMapper, UserSettingsHelper $userSettingsHelper) {
        $this->revisionMapper     = $revisionMapper;
        $this->userSettingsHelper = $userSettingsHelper;
    }

    /**
     * Check if the revision violates any custom rules enabled by the user
     *
     * @param PasswordRevision $revision
     *
     * @return array|null
     * @throws Exception
     */
    public function getRevisionSecurityLevel(PasswordRevision $revision): ?array {
        if($this->checkMaximumAgeInDaysRule($revision)) {
            return [PasswordSecurityCheckService::LEVEL_WEAK, PasswordSecurityCheckService::STATUS_OUTDATED];
        }

        if($this->checkDuplicateRule($revision)) {
            return [PasswordSecurityCheckService::LEVEL_WEAK, PasswordSecurityCheckService::STATUS_DUPLICATE];
        }

        return null;
    }

    /**
     * Check if the revision violates the users maximum age in days rule
     *
     * @param PasswordRevision $revision
     *
     * @return bool
     * @throws Exception
     */
    protected function checkMaximumAgeInDaysRule(PasswordRevision $revision): bool {
        $maxAgeInDays = $this->userSettingsHelper->get('password/security/age', $revision->getUserId());
        if($maxAgeInDays > 0 && time() - $maxAgeInDays * 86400 > $revision->getEdited()) {
            return true;
        }

        return false;
    }

    /**
     * Check if the revision violates the users duplicate rule
     *
     * @param PasswordRevision $revision
     *
     * @return bool
     * @throws Exception
     */
    protected function checkDuplicateRule(PasswordRevision $revision): bool {
        if(!empty($revision->getHash())) {
            $checkDuplicates = $this->userSettingsHelper->get('password/security/duplicates', $revision->getUserId());
            if($checkDuplicates && $this->revisionMapper->hasDuplicates($revision->getHash(), $revision->getModel(), $revision->getUserId())) {
                return true;
            }
        }

        return false;
    }
}