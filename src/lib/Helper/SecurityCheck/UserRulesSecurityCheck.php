<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\PasswordRevisionMapper;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;

/**
 * Class UserRulesSecurityCheck
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class UserRulesSecurityCheck {

    /**
     * @var PasswordRevisionMapper
     */
    protected $revisionMapper;

    /**
     * @var UserSettingsHelper
     */
    protected $userSettingsHelper;

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
     * @param PasswordRevision $revision
     *
     * @return array
     * @throws \Exception
     */
    public function getRevisionSecurityLevel(PasswordRevision $revision): ?array {
        $maxAgeInDays = $this->userSettingsHelper->get('password/security/age', $revision->getUserId());
        if($maxAgeInDays > 0 && time() - $maxAgeInDays * 86400 > $revision->getEdited()) {
            return [AbstractSecurityCheckHelper::LEVEL_WEAK, AbstractSecurityCheckHelper::STATUS_OUTDATED];
        }

        $checkDuplicates = $this->userSettingsHelper->get('password/security/duplicates', $revision->getUserId());
        if($checkDuplicates && $this->revisionMapper->hasDuplicates($revision->getHash(), $revision->getModel(), $revision->getUserId())) {
            return [AbstractSecurityCheckHelper::LEVEL_WEAK, AbstractSecurityCheckHelper::STATUS_DUPLICATE];
        }

        return null;
    }
}