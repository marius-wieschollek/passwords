<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
    protected $themingSettings;

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
        return
            $localisation->t('A new client with the name "%s" was connected successfully to Passwords with PassLink.', $client)
            .' '.
            $localisation->t('You can manage all connected devices and apps in the %s security section.', [$this->themingSettings->get('label')]);
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation): string {
        return $localisation->t('A new client was added');
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getSubject(IL10N $localisation): string {
        return $localisation->t('A new client was added');
    }
}