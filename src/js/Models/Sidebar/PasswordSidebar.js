/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Sidebar from "@js/Models/Sidebar/Sidebar";

export default class PasswordSidebar extends Sidebar {

    constructor(item, tab = null) {
        super('password', item, item.label, tab);
    }
}