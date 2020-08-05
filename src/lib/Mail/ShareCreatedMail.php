<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Mail;

use OC_Defaults;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\UserService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Mail\IMailer;

/**
 * Class ShareCreatedMail
 *
 * @package OCA\Passwords\Mail
 */
class ShareCreatedMail extends AbstractMail {

    const MAIL_ID   = 'share.created';
    const MAIL_TYPE = 'shares';

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * ShareCreatedMail constructor.
     *
     * @param IMailer        $mailer
     * @param OC_Defaults    $defaults
     * @param LoggingService $logger
     * @param UserService    $userService
     * @param IURLGenerator  $urlGenerator
     */
    public function __construct(
        IMailer $mailer,
        OC_Defaults $defaults,
        LoggingService $logger,
        UserService $userService,
        IURLGenerator $urlGenerator
    ) {
        $this->userService = $userService;
        parent::__construct($mailer, $defaults, $logger, $urlGenerator);
    }

    /**
     * @param IUser $user
     * @param IL10N $localisation
     * @param mixed ...$parameters
     */
    public function send(IUser $user, IL10N $localisation, ...$parameters): void {
        list($owners) = $parameters;
        list($passwordCount, $body) = $this->getBody($localisation, $owners);
        $title = $this->getTitle($localisation, $passwordCount);

        $template = $this->getTemplate();
        $template->addHeading($title);
        $template->addBodyText($body);

        $template->addBodyButton(
            $localisation->t('View passwords shared with me'),
            $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/shared/0'
        );

        $this->sendMessage($user, $title, $template);
    }

    /**
     * @param IL10N $localisation
     * @param int   $passwordCount
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation, int $passwordCount): string {
        return $localisation->n(
            'A password was shared with you on %s',
            'Several passwords were shared with you on %s',
            $passwordCount,
            [$this->defaults->getName()]
        );
    }

    /**
     * @param IL10N $localisation
     * @param array $owners
     *
     * @return array
     */
    protected function getBody(IL10N $localisation, array $owners): array {
        $ownerCount = count($owners);
        if($ownerCount === 1) {
            list($passwordCount, $body) = $this->getSingleOwnerBody($localisation, $owners);
        } else {
            list($passwordCount, $body) = $this->getMultiOwnerBody($localisation, $owners, $ownerCount);
        }

        $body .= ' '.$localisation->t('Open the passwords app to see '.($passwordCount === 1 ? 'it.':'them.'));

        return [$passwordCount, $body];
    }

    /**
     * @param IL10N $localisation
     * @param array $owners
     *
     * @return array
     */
    protected function getSingleOwnerBody(IL10N $localisation, array $owners): array {
        $ownerId       = key($owners);
        $owner         = $this->userService->getUserName($ownerId);
        $passwordCount = $owners[ $ownerId ];

        $body = $localisation->n('%s shared a password with you.', '%s shared %s passwords with you.', $passwordCount, [$owner, $passwordCount]);

        return [$passwordCount, $body];
    }

    /**
     * @param IL10N $localisation
     * @param array $owners
     * @param int   $ownerCount
     *
     * @return array
     */
    protected function getMultiOwnerBody(IL10N $localisation, array $owners, $ownerCount): array {
        $params        = [];
        $passwordCount = 0;

        foreach($owners as $ownerId => $amount) {
            if(count($params) < 4) $params[] = $this->userService->getUserName($ownerId);
            $passwordCount += $amount;
        }

        $params = array_reverse($params);
        array_unshift($params, $passwordCount, $ownerCount - 2);

        $text = ($ownerCount > 2 ? '%5$s, %4$s':'%4$s').' and '.($ownerCount > 3 ? '%2$s others':'%3$s').' shared %1$s passwords with you.';
        $body = $localisation->t($text, $params);

        return [$passwordCount, $body];
    }
}