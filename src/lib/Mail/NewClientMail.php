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

namespace OCA\Passwords\Mail;

use OC_Defaults;
use OCA\Passwords\Helper\Settings\ThemeSettingsHelper;
use OCA\Passwords\Services\LoggingService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IMailer;

/**
 * Class NewClientMail
 *
 * @package OCA\Passwords\Mail
 */
class NewClientMail extends AbstractMail {

    const MAIL_ID   = 'security.connect';
    const MAIL_TYPE = 'security';
    /**
     * @var ThemeSettingsHelper
     */
    protected ThemeSettingsHelper $themingSettings;

    /**
     * NewClientMail constructor.
     *
     * @param IMailer             $mailer
     * @param OC_Defaults         $defaults
     * @param LoggingService      $logger
     * @param IURLGenerator       $urlGenerator
     * @param ThemeSettingsHelper $themingSettings
     */
    public function __construct(IMailer $mailer, OC_Defaults $defaults, LoggingService $logger, IURLGenerator $urlGenerator, ThemeSettingsHelper $themingSettings) {
        parent::__construct($mailer, $defaults, $logger, $urlGenerator);
        $this->themingSettings = $themingSettings;
    }

    /**
     * @param IUser $user
     * @param IL10N $localisation
     * @param mixed ...$parameters
     */
    public function send(IUser $user, IL10N $localisation, ...$parameters): void {
        [$client] = $parameters;

        $template = $this->getTemplate();

        $template->addHeading(
            $this->getTitle($localisation)
        );

        $template->addBodyText(
            $this->getBody($localisation, $client)
        );

        $template->addBodyButton(
            $localisation->t('Manage devices & apps'),
            $this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security'])
        );

        $subject = $this->getSubject($localisation);

        $this->sendMessage($user, $subject, $template);
    }

    /**
     * @param IL10N  $localisation
     * @param string $client
     *
     * @return string
     */
    protected function getBody(IL10N $localisation, string $client): string {
        $label = $this->themingSettings->get('label');

        return
            $localisation->t('"%s" was granted access to your %s Passwords account via PassLink.', [$client, $label])
            .' '.
            $localisation->t('You can manage all connected devices and apps in your %s settings in the security section.', [$label]);
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation): string {
        return $localisation->t('A new client or app was connected to your account');
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getSubject(IL10N $localisation): string {
        return $localisation->t('A new client or app was connected to your account');
    }
}