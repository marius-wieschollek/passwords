<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Link;

use Exception;
use OC\Authentication\Token\IToken;
use OCA\Passwords\Db\Registration;
use OCA\Passwords\Helper\Settings\ServerSettingsHelper;
use OCA\Passwords\Helper\Token\ApiTokenHelper;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\MailService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\Services\Object\RegistrationService;
use OCA\Passwords\Services\SessionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Throwable;

/**
 * Class ConnectController
 *
 * @package OCA\Passwords\Controller\Link
 */
class ConnectController extends Controller {

    const PASSLINK_CONNECT = "ext+passlink:%s/do/connect?id=%s&theme=%s";
    const SESSION_KEY      = 'passlink.connect';

    /**
     * @var MailService
     */
    protected $mails;

    /**
     * @var SessionService
     */
    protected $session;

    /**
     * @var ApiTokenHelper
     */
    protected $tokenHelper;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * @var ServerSettingsHelper
     */
    protected $serverSettings;

    /**
     * @var RegistrationService
     */
    protected $registrationService;

    /**
     * ConnectController constructor.
     *
     * @param                      $appName
     * @param RegistrationService  $registrationService
     * @param ServerSettingsHelper $serverSettings
     * @param NotificationService  $notifications
     * @param EnvironmentService   $environment
     * @param ApiTokenHelper       $tokenHelper
     * @param SessionService       $session
     * @param MailService          $mails
     * @param IRequest             $request
     */
    public function __construct(
        $appName,
        RegistrationService $registrationService,
        ServerSettingsHelper $serverSettings,
        NotificationService $notifications,
        EnvironmentService $environment,
        ApiTokenHelper $tokenHelper,
        SessionService $session,
        MailService $mails,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
        $this->registrationService = $registrationService;
        $this->serverSettings      = $serverSettings;
        $this->notifications       = $notifications;
        $this->tokenHelper         = $tokenHelper;
        $this->environment         = $environment;
        $this->session             = $session;
        $this->mails               = $mails;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @UserRateThrottle(limit=2, period=60)
     *
     * @return JSONResponse
     */
    public function request(): JSONResponse {
        $this->destroyCurrentRegistration();

        $registration = $this->registrationService->create();
        $this->registrationService->save($registration);
        $this->session->set(self::SESSION_KEY, $registration->getUuid());
        $this->session->save();

        $baseUrl = $this->serverSettings->get('baseUrl');
        $linkUrl = str_replace('https://', '', $this->serverSettings->get('baseUrl'));
        $theme   = $this->getTheme($baseUrl);
        $link    = sprintf(self::PASSLINK_CONNECT, $linkUrl, $registration->getUuid(), $theme);

        $data = [
            'id'   => $registration->getUuid(),
            'link' => $link
        ];

        return new JSONResponse($data);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function await(): JSONResponse {
        $time  = 0;
        $limit = $this->getTimeLimit() * 4;
        while($time < $limit) {
            $registration = $this->getRegistrationFromSession();
            if($registration === null) {
                return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
            }

            if($registration->getStatus() === 1) {
                $time = $registration->getUpdated() + $registration->getLimit() - time() - 1;
                $data = [
                    'label' => $registration->getLabel(),
                    'time'  => $time,
                    'code'  => explode(',', $registration->getCode())
                ];

                return new JSONResponse($data);
            }

            usleep(250000);
            $time++;
        }

        $this->destroyCurrentRegistration();

        return new JSONResponse(['success' => false], Http::STATUS_FAILED_DEPENDENCY);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function reject(): JSONResponse {
        $registration = $this->getRegistrationFromSession();
        if($registration === null || $registration->getStatus() !== 1) {
            return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
        }

        $registration->setStatus(3);
        $this->registrationService->save($registration);

        return new JSONResponse(['success' => true]);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     *
     * @return JSONResponse
     */
    public function confirm(string $label = null): JSONResponse {
        $registration = $this->getRegistrationFromSession();
        if($registration === null || $registration->getStatus() !== 1) {
            return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
        }

        $label = $this->validateLabel($label, $registration->getLabel());

        try {
            /** @var $deviceToken IToken */
            [$token, $deviceToken] = $this->tokenHelper->createToken($label, true);
        } catch(Throwable $e) {
            return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
        }

        $registration->setStatus(2);
        $registration->setLogin($deviceToken->getLoginName());
        $registration->setToken($token);
        $this->registrationService->save($registration);
        $this->notifications->sendNewClientNotification($registration->getUserId(), $label);
        $this->mails->sendNewClientMail($registration->getUserId(), $label);

        return new JSONResponse(['success' => true]);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @UserRateThrottle(limit=2, period=60)
     *
     * @param string $id
     * @param array  $codes
     * @param string $label
     *
     * @return JSONResponse
     * @throws Exception
     */
    public function apply(string $id, array $codes, string $label = null): JSONResponse {
        try {
            /** @var Registration $registration */
            $registration = $this->registrationService->findByUuid($id);
        } catch(Throwable $e) {
            return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
        }

        if($registration->getStatus() !== 0) {
            return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
        }

        if(!$this->validateCodes($codes)) {
            return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
        }

        $label = $this->validateLabel($label);

        try {
            $registration->setLimit($this->getTimeLimit());
            $registration->setStatus(1);
            $registration->setLabel($label);
            $registration->setCode(implode(',', $codes));
            $this->registrationService->save($registration);
        } catch(Throwable $e) {
            return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
        }

        return $this->waitForConfirmation($id);
    }

    /**
     * @return Registration|null
     */
    protected function getRegistrationFromSession(): ?Registration {
        if($this->session->has(self::SESSION_KEY)) {
            $id = $this->session->get(self::SESSION_KEY);

            $this->registrationService->clearCache();
            try {
                $registration = $this->registrationService->findByUuid($id);
            } catch(Throwable $e) {
                return null;
            }

            if($registration->getCreated() > time() - 121) {
                return $registration;
            }

            $this->destroyCurrentRegistration();
        }

        return null;
    }

    /**
     *
     */
    protected function destroyCurrentRegistration(): void {
        if($this->session->has(self::SESSION_KEY)) {
            $id = $this->session->get(self::SESSION_KEY);

            try {
                $registration = $this->registrationService->findByUuid($id);
                $this->registrationService->destroy($registration);
            } catch(Throwable $e) {
            }
        }
    }

    /**
     * @param string|null $baseUrl
     *
     * @return string
     */
    protected function getTheme(?string $baseUrl): string {
        return urlencode(
            base64_encode(
                gzcompress(
                    json_encode(
                        [
                            'label'      => $this->serverSettings->get('theme.label'),
                            'logo'       => str_replace($baseUrl, '', $this->serverSettings->get('theme.logo')),
                            'background' => str_replace($baseUrl, '', $this->serverSettings->get('theme.background')),
                            'color'      => $this->serverSettings->get('theme.color.primary'),
                            'txtColor'   => $this->serverSettings->get('theme.color.text'),
                            'bgColor'    => $this->serverSettings->get('theme.color.background'),
                        ]
                    ),
                    9
                )
            )
        );
    }

    /**
     * @param array $code
     *
     * @return bool
     */
    protected function validateCodes(array $code): bool {
        $code = array_unique(array_map('trim', $code));

        if(count($code) < 3 || count($code) > 4) {
            return false;
        }

        foreach($code as $value) {
            if(mb_strlen($value) < 4 || mb_strlen($value) > 6 || !preg_match('/^(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string|null $label
     * @param string|null $fallback
     *
     * @return string
     */
    protected function validateLabel(?string $label, ?string $fallback = null): string {
        $label = $label === null ? '':trim($label);

        if(empty($label) ||
           in_array($label, $this->environment->getProtectedClients()) ||
           strpos($label, 'Passwords Session') !== false ||
           !preg_match('/^[\w\s-]{12,48}$/', $label)) {
            return $fallback === null ? $this->environment->getUserAgent():$fallback;
        }

        if(strlen($label) > 256) return substr($label, 0, 256);

        return $label;
    }

    /**
     * @param string $id
     *
     * @return JSONResponse
     * @throws Exception
     */
    protected function waitForConfirmation(string $id): JSONResponse {
        $time  = 0;
        $limit = $this->getTimeLimit() * 4;
        while($time < $limit) {
            $this->registrationService->clearCache();
            try {
                /** @var Registration $registration */
                $registration = $this->registrationService->findByUuid($id);
            } catch(Throwable $e) {
                return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
            }

            if($registration->getStatus() === 2) {
                $data = [
                    'success' => true,
                    'login'   => $registration->getLogin(),
                    'token'   => $registration->getToken()
                ];
                try {
                    $this->registrationService->destroy($registration);
                } catch(Exception $e) {
                }

                return new JSONResponse($data);
            } else if($registration->getStatus() === 3) {
                $this->registrationService->destroy($registration);
                break;
            }

            usleep(250000);
            $time++;
        }

        return new JSONResponse(['success' => false], Http::STATUS_FAILED_DEPENDENCY);
    }

    /**
     * @return int
     */
    protected function getTimeLimit(): int {
        set_time_limit(0);
        $maxExecutionTime = intval(ini_get('max_execution_time'));

        return $maxExecutionTime === 0 || $maxExecutionTime > 59 ? 59:$maxExecutionTime - 1;
    }
}