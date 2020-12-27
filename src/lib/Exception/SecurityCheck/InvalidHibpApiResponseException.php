<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Exception\SecurityCheck;

use Exception;
use GuzzleHttp\Exception\ClientException;
use OCP\Http\Client\IResponse;
use Throwable;

/**
 * Class InvalidHibpApiResponseException
 *
 * @package OCA\Passwords\Exception\SecurityCheck
 */
class InvalidHibpApiResponseException extends Exception {

    const EXCEPTION_CODE = 105;

    /**
     * InvalidHibpApiResponseException constructor.
     *
     * @param IResponse|null  $response
     * @param Throwable|null $previous
     */
    public function __construct(IResponse $response = null, Throwable $previous = null) {
        $message = 'HIBP API returned invalid response';
        if($response instanceof IResponse) {
            $message .= " HTTP {$response->getStatusCode()}";
        } else if($previous instanceof ClientException) {
            $message .= " HTTP {$previous->getResponse()->getStatusCode()}";
        } else if($previous instanceof Throwable) {
            $message .= $previous->getMessage();
        }

        parent::__construct($message, static::EXCEPTION_CODE, $previous);
    }
}