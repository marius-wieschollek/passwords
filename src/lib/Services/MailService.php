<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OC_Defaults;
use OCA\Passwords\AppInfo\Application;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Util;

/**
 * Class MailService
 *
 * @package OCA\Passwords\Services
 */
class MailService {

    /**
     * @var IMailer
     */
    protected $mailer;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var SettingsService
     */
    protected $settings;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IFactory
     */
    protected $l10NFactory;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var array
     */
    protected $sender;

    /**
     * @var OC_Defaults
     */
    protected $defaults;

    /**
     * MailService constructor.
     *
     * @param IMailer         $mailer
     * @param IConfig         $config
     * @param OC_Defaults     $defaults
     * @param IFactory        $l10NFactory
     * @param LoggingService  $logger
     * @param IUserManager    $userManager
     * @param SettingsService $settings
     * @param IURLGenerator   $urlGenerator
     */
    public function __construct(
        IMailer $mailer,
        IConfig $config,
        OC_Defaults $defaults,
        IFactory $l10NFactory,
        LoggingService $logger,
        IUserManager $userManager,
        SettingsService $settings,
        IURLGenerator $urlGenerator
    ) {
        $this->mailer       = $mailer;
        $this->logger       = $logger;
        $this->config       = $config;
        $this->settings     = $settings;
        $this->userManager  = $userManager;
        $this->l10NFactory  = $l10NFactory;
        $this->urlGenerator = $urlGenerator;
        $this->defaults     = $defaults;
    }

    /**
     * @param string $userId
     * @param int    $passwords
     *
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function sendBadPasswordMail(string $userId, int $passwords) {
        if(!$this->settings->get('user.mail.security', $userId)) return;
        $user         = $this->userManager->get($userId);
        $localisation = $this->getLocalisation($userId);

        $subject = $localisation->n('You have an insecure password', 'You have insecure passwords', $passwords);
        $title   = $localisation->n('One of your passwords is no longer secure', 'Some of your passwords are no longer secure', $passwords);

        $body = [
            $localisation->t('Passwords regularly checks if your passwords have been compromised by a data breach.'),
            $localisation->n(
                'This security check has found that one of your passwords is insecure.',
                'This security check has found that %s of your passwords are insecure.',
                $passwords,
                [$passwords]
            ),
            $localisation->n(
                'That means that the password is out on the internet and puts your account at risk.',
                'That means that the passwords are out on the internet and puts your accounts at risk.',
                $passwords
            ),
            $localisation->n(
                'Therefore the password has been marked as insecure and should be changed now.',
                'Therefore the passwords have been marked as insecure and should be changed now.',
                $passwords
            ),
            $localisation->n(
                'You can create a new secure password in the passwords app.',
                'You can create new secure passwords in the passwords app.',
                $passwords
            )
        ];

        $button = [
            'text' => $localisation->n('Change password now', 'Change passwords now', $passwords),
            'url'  => $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/security/2'
        ];

        $this->sendMail($user, $subject, $title, implode(' ', $body), $button);
    }

    /**
     * @param string $userId
     * @param array  $owners
     *
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function sendShareCreateMail(string $userId, array $owners) {
        if(!$this->settings->get('user.mail.shares', $userId)) return;
        $user          = $this->userManager->get($userId);
        $localisation  = $this->getLocalisation($userId);
        $ownerCount    = count($owners);
        $passwordCount = 0;

        if($ownerCount === 1) {
            $ownerId       = key($owners);
            $owner         = $this->userManager->get($ownerId)->getDisplayName();
            $passwordCount = $owners[ $ownerId ];

            $body = $localisation->n('%s shared a password with you.', '%s shared %s passwords with you.', $passwordCount, [$owner, $passwordCount]);
        } else {
            $params = [];
            foreach($owners as $ownerId => $amount) {
                if(count($params) < 4) $params[] = $this->userManager->get($ownerId)->getDisplayName();
                $passwordCount += $amount;
            }
            $params = array_reverse($params);
            array_unshift($params, $passwordCount, $ownerCount - 2);

            $text = ($ownerCount > 2 ? '%5$s, %4$s':'%4$s').' and '.($ownerCount > 3 ? '%2$s others':'%3$s').' shared %1$s passwords with you.';
            $body = $localisation->t($text, $params);
        }
        $body .= ' '.$localisation->t('Open the passwords app to see '.($passwordCount === 1 ? 'it.':'them.'));

        $button = [
            'text' => $localisation->t('View passwords shared with me'),
            'url'  => $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/shared/0'
        ];

        $title = $localisation->n(
            'A password was shared with you on %s',
            'Several passwords were shared with you on %s',
            $passwordCount,
            [$this->defaults->getName()]
        );

        $this->sendMail($user, $title, $title, $body, $button);
    }

    /**
     * @param IUser  $user
     * @param string $subject
     * @param string $title
     * @param string $body
     *
     * @return bool
     */
    protected function sendMail(IUser $user, string $subject, string $title, string $body, ?array $button = null) {
        if($user->getEMailAddress() === null) return false;
        $template = $this->mailer->createEMailTemplate('passwords.EMail');

        $template->addHeader();
        $template->addHeading($title);
        $template->addBodyText($body);
        if($button !== null) $template->addBodyButton($button['text'], $button['url']);
        $template->addFooter();

        $message = $this->mailer->createMessage();
        $message->setTo([$user->getEMailAddress() => $user->getDisplayName()]);
        $message->setSubject($subject);
        $message->setHtmlBody($template->renderHtml());
        $message->setPlainBody($template->renderText());
        $message->setFrom($this->getSenderData());

        try {
            $this->mailer->send($message);
        } catch(\Exception $e) {
            $this->logger->logException($e);

            return false;
        }

        return true;
    }

    /**
     * Get the sender data
     *
     * @return array
     */
    protected function getSenderData() {
        if(!$this->sender) {
            $this->sender = [Util::getDefaultEmailAddress('no-reply') => $this->defaults->getName()];
        }

        return $this->sender;
    }

    /**
     * @param string $userId
     *
     * @return IL10N
     */
    protected function getLocalisation(string $userId): IL10N {
        $lang         = $this->config->getUserValue($userId, 'core', 'lang');
        $localisation = $this->l10NFactory->get(Application::APP_NAME, $lang);

        return $localisation;
    }
}