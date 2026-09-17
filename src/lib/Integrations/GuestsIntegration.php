<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Integrations;

use OC;
use OCA\Guests\UserBackend;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\IGroupManager;

class GuestsIntegration {

    /**
     * @param IGroupManager        $groupManager
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     */
    public function __construct(
        protected IGroupManager        $groupManager,
        protected ConfigurationService $config,
        protected EnvironmentService   $environment
    ) {
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return $this->config->isAppEnabled('guests');
    }

    /**
     * If the current user is a guest
     *
     * @return bool
     */
    public function isCurrentUserAGuest(): bool {
        if($this->isAvailable()) {
            // @TODO: Use container instead
            $guestBackend = OC::$server->get(UserBackend::class);

            return $guestBackend->userExists($this->environment->getUserId());
        }

        return false;
    }

    /**
     * If the current user is a guest
     * and restricted to sharing only with
     * other users from his groups
     *
     * @return bool
     */
    public function hasCurrentUserGuestGroupSharingRestriction(): bool {
        return $this->config->getAppValueBool('hide_users', true, 'guests') &&
               $this->isCurrentUserAGuest();
    }
}