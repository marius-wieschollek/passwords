/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import LocalisationService from "@js/Services/LocalisationService";

export default class CreateShareError extends Error {

    constructor(message) {
        message = LocalisationService.translateArray(message);
        super(message);
    }
}