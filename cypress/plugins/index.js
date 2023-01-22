/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import {cypressBrowserPermissionsPlugin} from 'cypress-browser-permissions';

module.exports = (on, config) => {
    return cypressBrowserPermissionsPlugin(on, config);
};