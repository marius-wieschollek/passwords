<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Mail;

use OCP\IL10N;
use OCP\IUser;

/**
 * Class BadPasswordMail
 *
 * @package OCA\Passwords\Mail
 */
class BadPasswordMail extends AbstractMail {

    const MAIL_ID   = 'security.password';
    const MAIL_TYPE = 'security';

    /**
     * @param IUser $user
     * @param IL10N $localisation
     * @param mixed ...$parameters
     */
    public function send(IUser $user, IL10N $localisation, ...$parameters): void {
        list($passwords) = $parameters;

        $template = $this->getTemplate();

        $template->addHeading(
            $this->getTitle($localisation, $passwords)
        );

        $template->addBodyText(
            $this->getBody($localisation, $passwords)
        );

        $template->addBodyButton(
            $localisation->n('Change password now', 'Change passwords now', $passwords),
            $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/security/2'
        );

        $subject = $this->getSubject($localisation, $passwords);

        $this->sendMessage($user, $subject, $template);
    }

    /**
     * @param IL10N $localisation
     * @param int   $passwords
     *
     * @return string
     */
    protected function getBody(IL10N $localisation, int $passwords): string {
        return
            $localisation->t('Passwords regularly checks if your passwords have been compromised by a data breach.')
            .' '.
            $localisation->n(
                'This security check has found that one of your passwords is insecure.',
                'This security check has found that %s of your passwords are insecure.',
                $passwords,
                [$passwords]
            )
            .' '.
            $localisation->n(
                'That means that the password is out on the internet and puts your account at risk.',
                'That means that the passwords are out on the internet and puts your accounts at risk.',
                $passwords
            )
            .' '.
            $localisation->n(
                'Therefore the password has been marked as insecure and should be changed now.',
                'Therefore the passwords have been marked as insecure and should be changed now.',
                $passwords
            )
            .' '.
            $localisation->n(
                'You can create a new secure password in the passwords app.',
                'You can create new secure passwords in the passwords app.',
                $passwords
            );
    }

    /**
     * @param IL10N $localisation
     * @param int   $passwords
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation, int $passwords): string {
        return trim($localisation->n(
            'One of your passwords is no longer secure.',
            'Some of your passwords are no longer secure.',
            $passwords
        ), '.');
    }

    /**
     * @param IL10N $localisation
     * @param int   $passwords
     *
     * @return string
     */
    protected function getSubject(IL10N $localisation, int $passwords): string {
        return $localisation->n(
            'You have an insecure password',
            'You have insecure passwords',
            $passwords
        );
    }
}