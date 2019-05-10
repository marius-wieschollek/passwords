<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Mail;

use OC_Defaults;
use OCA\Passwords\Services\LoggingService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Util;

/**
 * Class AbstractMail
 *
 * @package OCA\Passwords\Mail
 */
abstract class AbstractMail {

    const MAIL_ID   = self::MAIL_ID;
    const MAIL_TYPE = self::MAIL_TYPE;

    /**
     * @var IMailer
     */
    protected $mailer;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var OC_Defaults
     */
    protected $defaults;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * AbstractMail constructor.
     *
     * @param IMailer        $mailer
     * @param OC_Defaults    $defaults
     * @param LoggingService $logger
     * @param IURLGenerator  $urlGenerator
     */
    public function __construct(
        IMailer $mailer,
        OC_Defaults $defaults,
        LoggingService $logger,
        IURLGenerator $urlGenerator
    ) {
        $this->mailer       = $mailer;
        $this->logger       = $logger;
        $this->defaults     = $defaults;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Create and send the mail to the user
     *
     * @param IUser $user
     * @param IL10N $localisation
     * @param mixed ...$parameters
     */
    abstract function send(IUser $user, IL10N $localisation, ...$parameters): void;

    /**
     * Create a new mail template
     *
     * @return IEMailTemplate
     */
    protected function getTemplate() {
        $template = $this->mailer->createEMailTemplate('passwords.EMail.'.static::MAIL_ID);

        $template->addHeader();

        return $template;
    }

    /**
     * Send the mail
     *
     * @param IUser          $user
     * @param string         $subject
     * @param IEMailTemplate $template
     */
    protected function sendMessage(IUser $user, string $subject, IEMailTemplate $template): void {
        $template->addFooter();

        $message = $this->mailer->createMessage();
        $message->setTo([$user->getEMailAddress() => $user->getDisplayName()]);
        $message->setFrom([Util::getDefaultEmailAddress('no-reply') => $this->defaults->getName()]);
        $message->setSubject($subject);
        $message->setHtmlBody($template->renderHtml());
        $message->setPlainBody($template->renderText());

        try {
            $this->mailer->send($message);
        } catch(\Exception $e) {
            $this->logger->logException($e);
        }
    }
}