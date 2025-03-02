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

namespace OCA\Passwords\Settings;

use OCA\Passwords\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

class UserSettings implements ISettings {

    /**
     * @param IRequest      $request
     * @param IURLGenerator $urlGenerator
     */
    public function __construct(
        protected IRequest      $request,
        protected IURLGenerator $urlGenerator
    ) {
    }

    /**
     * @return TemplateResponse
     */
    public function getForm() {
        $tokenCreated = $this->request->getParam('pwAppToken', 'false');
        $url = $this->urlGenerator->linkToRoute('passwords.user_settings.createAppToken');

        return new TemplateResponse(Application::APP_NAME, 'user/index', ['url' => $url, 'created' => $tokenCreated], 'blank');
    }

    /**
     * @return string
     */
    public function getSection() {
        return 'security';
    }

    /**
     * @return int
     */
    public function getPriority() {
        return 999;
    }
}