<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services\Traits;

trait ValidatesDomainTrait {

    protected function validateDomain(string $domain): string {
        if(filter_var($domain, FILTER_VALIDATE_URL)) $domain = parse_url($domain, PHP_URL_HOST);
        if(filter_var($domain, FILTER_VALIDATE_DOMAIN)) $domain = filter_var($domain, FILTER_VALIDATE_DOMAIN);
        if(filter_var($domain, FILTER_VALIDATE_IP)) $domain = filter_var($domain, FILTER_VALIDATE_IP);
        return idn_to_ascii($domain);
    }
}