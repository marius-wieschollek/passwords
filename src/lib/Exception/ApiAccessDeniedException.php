<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.12.17
 * Time: 23:04
 */

namespace OCA\Passwords\Exception;

/**
 * Class ApiAccessDeniedException
 *
 * @package OCA\Passwords\Exception
 */
class ApiAccessDeniedException extends ApiException {
    public function __construct() {
        parent::__construct("Access denied", 401);
    }
}