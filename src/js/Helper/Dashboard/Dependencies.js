/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import API from '@js/Helper/api';
import SettingsService from '@js/Services/SettingsService';
import Vue from "vue";
import Dashboard  from "@vue/Dashboard/Dashboard";

export default class Dependencies {

    get api() {
        return API;
    }

    get settingsService() {
        return SettingsService;
    }

    get vue() {
        return Vue;
    }

    get dashboardWidget() {
        return Dashboard;
    }
}