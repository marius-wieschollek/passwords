<?php
/*
 * @copyright 2025 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller\User;

use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class SettingsController extends Controller {

    /**
     * @param                $appName
     * @param IRequest       $request
     * @param ApiTokenHelper $tokenHelper
     * @param IURLGenerator  $urlGenerator
     */
    public function __construct(
        $appName,
        IRequest $request,
        protected ApiTokenHelper $tokenHelper,
        protected IURLGenerator $urlGenerator
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @return RedirectResponse
     */
    #[UseSession]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function createAppToken(): RedirectResponse {
        $this->tokenHelper->createStaticWebUiToken();
        $backUrl = $this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security', 'pwAppToken' => 'true']);

        return new RedirectResponse($backUrl);
    }
}