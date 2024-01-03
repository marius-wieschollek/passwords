import Vue from 'vue';
import Router from 'vue-router';
import SectionTags from '@vue/Section/Tags';
import SectionRecent from '@vue/Section/Recent';
import SectionFolders from '@vue/Section/Folders';
import Localisation from '@js/Classes/Localisation';
import SectionFavorites from '@vue/Section/Favorites';
import Logger from "@js/Classes/Logger";
import MessageService from "@js/Services/MessageService";

function handleChunkLoadingError(e, module) {
    Logger.error(e);
    MessageService
        .alert(['Unable to load {module}', {module}], 'Network error')
        .finally(() => {
            router.push('/');
        });
    throw e;
}

const SectionTrash = async () => {
    try {
        return await import(/* webpackChunkName: "TrashSection" */ '@vue/Section/Trash');
    } catch(e) {
        handleChunkLoadingError(e, 'TrashSection');
    }
};

const SectionSecurity = async () => {
    try {
        return await import(/* webpackChunkName: "SecuritySection" */ '@vue/Section/Security');
    } catch(e) {
        handleChunkLoadingError(e, 'SecuritySection');
    }
};

const SectionSearch = async () => {
    try {
        return await import(/* webpackChunkName: "SearchSection" */ '@vue/Section/Search');
    } catch(e) {
        handleChunkLoadingError(e, 'SearchSection');
    }
};

const SectionShared = async () => {
    try {
        return await import(/* webpackChunkName: "SharedSection" */ '@vue/Section/Shared');
    } catch(e) {
        handleChunkLoadingError(e, 'SharedSection');
    }
};

const SectionAuthorize = async () => {
    try {
        return await import(/* webpackChunkName: "AuthorizeSection" */ '@vue/Section/Authorize');
    } catch(e) {
        handleChunkLoadingError(e, 'AuthorizeSection');
    }
};

const SectionHelp = async () => {
    try {
        return await import(/* webpackChunkName: "HelpSection" */ '@vue/Section/Help');
    } catch(e) {
        handleChunkLoadingError(e, 'HelpSection');
    }
};

const SectionBackup = async () => {
    try {
        let section      = import(/* webpackChunkName: "BackupSection" */ '@vue/Section/Backup'),
            translations = Localisation.loadSection('backups');

        await Promise.all([section, translations]);

        return section;
    } catch(e) {
        handleChunkLoadingError(e, 'BackupSection');
    }
};

const SectionSettings = async () => {
    try {
        let section      = import(/* webpackChunkName: "SettingsSection" */ '@vue/Section/Settings'),
            translations = Localisation.loadSection('settings');

        await Promise.all([section, translations]);

        return section;
    } catch(e) {
        handleChunkLoadingError(e, 'SettingsSection');
    }
};

const SectionApps = async () => {
    try {
        let section      = import(/* webpackChunkName: "AppsSection" */ '@vue/Section/Apps'),
            translations = Localisation.loadSection('apps');

        await Promise.all([section, translations]);

        return section;
    } catch(e) {
        handleChunkLoadingError(e, 'AppsSection');
    }
};

Vue.use(Router);
let router = new Router(
    {
        routes: [
            {name: 'Folders', path: '/folders/:folder?', components: {main: SectionFolders}},
            {name: 'Tags', path: '/tags/:tag?', components: {main: SectionTags}},
            {name: 'Recent', path: '/recent', components: {main: SectionRecent}},
            {name: 'Favorites', path: '/favorites', components: {main: SectionFavorites}},
            {name: 'Shares', path: '/shared/:type?', components: {main: SectionShared}},
            {name: 'Security', path: '/security/:status?', components: {main: SectionSecurity}},
            {name: 'Search', path: '/search/:query?', components: {main: SectionSearch}},
            {name: 'Trash', path: '/trash', components: {main: SectionTrash}},
            {name: 'Settings', path: '/settings', components: {main: SectionSettings}},
            {name: 'Authorize', path: '/authorize/:target?', components: {main: SectionAuthorize}},
            {name: 'Backup', path: '/backup/:action?', components: {main: SectionBackup}},
            {name: 'Help', path: '/help/:page?', components: {main: SectionHelp}},
            {name: 'Apps and Extensions', path: '/apps', components: {main: SectionApps}}
        ]
    }
);

export default router;