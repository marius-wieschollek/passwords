<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCP\Defaults;
use OCP\IConfig;
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
     * MailService constructor.
     *
     * @param IMailer        $mailer
     * @param IConfig        $config
     * @param IFactory       $l10NFactory
     * @param LoggingService $logger
     * @param IUserManager   $userManager
     * @param IURLGenerator  $urlGenerator
     */
    public function __construct(
        IMailer $mailer,
        IConfig $config,
        IFactory $l10NFactory,
        LoggingService $logger,
        IUserManager $userManager,
        IURLGenerator $urlGenerator
    ) {
        $this->mailer       = $mailer;
        $this->logger       = $logger;
        $this->userManager  = $userManager;
        $this->l10NFactory  = $l10NFactory;
        $this->config       = $config;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $userId
     * @param int    $passwords
     */
    public function sendBadPasswordMail(string $userId, int $passwords) {
        $user         = $this->userManager->get($userId);
        $lang         = $this->config->getUserValue($userId, 'core', 'lang');
        $localisation = $this->l10NFactory->get(Application::APP_NAME, $lang);

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
            $defaults     = new Defaults();
            $this->sender = [Util::getDefaultEmailAddress('no-reply') => $defaults->getName()];
        }

        return $this->sender;
    }
}