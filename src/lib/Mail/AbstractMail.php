<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Mail;

use OC_Defaults;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
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
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var OC_Defaults
     */
    protected $defaults;

    /**
     * AbstractMail constructor.
     *
     * @param IMailer       $mailer
     * @param OC_Defaults   $defaults
     * @param IURLGenerator $urlGenerator
     */
    public function __construct(
        IMailer $mailer,
        OC_Defaults $defaults,
        IURLGenerator $urlGenerator
    ) {
        $this->mailer       = $mailer;
        $this->defaults     = $defaults;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param IUser $user
     * @param IL10N $localisation
     * @param mixed ...$parameters
     *
     * @return IMessage
     */
    abstract function create(IUser $user, IL10N $localisation, ...$parameters): IMessage;

    /**
     * @return IEMailTemplate
     */
    protected function getTemplate() {
        $template = $this->mailer->createEMailTemplate('passwords.EMail.'.static::MAIL_ID);

        $template->addHeader();

        return $template;
    }

    /**
     * @param IUser          $user
     * @param string         $subject
     * @param IEMailTemplate $template
     *
     * @return IMessage
     */
    protected function getMail(IUser $user, string $subject, IEMailTemplate $template): IMessage {
        $template->addFooter();

        $message = $this->mailer->createMessage();
        $message->setTo([$user->getEMailAddress() => $user->getDisplayName()]);
        $message->setFrom([Util::getDefaultEmailAddress('no-reply') => $this->defaults->getName()]);
        $message->setSubject($subject);
        $message->setHtmlBody($template->renderHtml());
        $message->setPlainBody($template->renderText());

        return $message;
    }
}