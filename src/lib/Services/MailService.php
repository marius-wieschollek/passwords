<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Mail\AbstractMail;
use OCA\Passwords\Mail\BadPasswordMail;
use OCA\Passwords\Mail\NewClientMail;
use OCA\Passwords\Mail\ShareCreatedMail;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\L10N\IFactory;

/**
 * Class MailService
 *
 * @package OCA\Passwords\Services
 */
class MailService {

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var UserSettingsService
     */
    protected $settings;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var IFactory
     */
    protected $l10NFactory;

    /**
     * @var NewClientMail
     */
    protected $newClientMail;

    /**
     * @var BadPasswordMail
     */
    protected $badPasswordMail;

    /**
     * @var ShareCreatedMail
     */
    protected $shareCreatedMail;

    /**
     * MailService constructor.
     *
     * @param IConfig             $config
     * @param IFactory            $l10NFactory
     * @param LoggingService      $logger
     * @param UserService         $userService
     * @param UserSettingsService $settings
     * @param NewClientMail       $newClientMail
     * @param BadPasswordMail     $badPasswordMail
     * @param ShareCreatedMail    $shareCreatedMail
     */
    public function __construct(
        IConfig $config,
        IFactory $l10NFactory,
        LoggingService $logger,
        UserService $userService,
        NewClientMail $newClientMail,
        UserSettingsService $settings,
        BadPasswordMail $badPasswordMail,
        ShareCreatedMail $shareCreatedMail
    ) {
        $this->logger           = $logger;
        $this->config           = $config;
        $this->settings         = $settings;
        $this->userService      = $userService;
        $this->l10NFactory      = $l10NFactory;
        $this->newClientMail    = $newClientMail;
        $this->badPasswordMail  = $badPasswordMail;
        $this->shareCreatedMail = $shareCreatedMail;
    }

    /**
     * @param string $userId
     * @param int    $passwords
     */
    public function sendBadPasswordMail(string $userId, int $passwords): void {
        $this->createAndSendMail($userId, $this->badPasswordMail, $passwords);
    }

    /**
     * @param string $userId
     * @param array  $owners
     */
    public function sendShareCreateMail(string $userId, array $owners): void {
        $this->createAndSendMail($userId, $this->shareCreatedMail, $owners);
    }

    /**
     * @param string $userId
     * @param string $client
     */
    public function sendNewClientMail(string $userId, string $client): void {
        $this->createAndSendMail($userId, $this->newClientMail, $client);
    }

    /**
     * @param string       $userId
     * @param AbstractMail $mail
     * @param mixed        ...$parameters
     */
    protected function createAndSendMail(string $userId, AbstractMail $mail, ...$parameters): void {
        $user = $this->getReceivingUser($userId, $mail::MAIL_TYPE);
        if($user === null) return;

        $localisation = $this->getLocalisation($userId);

        $mail->send($user, $localisation, ...$parameters);
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

    /**
     * @param string $userId
     * @param string $mailType
     *
     * @return null|IUser
     */
    protected function getReceivingUser(string $userId, string $mailType): ?IUser {
        try {
            if(!$this->settings->get('user.mail.'.$mailType, $userId)) return null;
        } catch(Exception $e) {
            $this->logger->logException($e);
        }

        $user = $this->userService->getUser($userId);
        if(empty($user->getEMailAddress())) return null;

        return $user;
    }
}