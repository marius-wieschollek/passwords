import Vue from 'vue';
import Router from 'vue-router';
import Messages from '@js/Classes/Messages';
import SectionTags from '@vue/Section/Tags';
import SectionTrash from '@vue/Section/Trash';
import SectionRecent from '@vue/Section/Recent';
import SectionShared from '@vue/Section/Shared';
import SectionSearch from '@vue/Section/Search';
import SectionFolders from '@vue/Section/Folders';
import SectionSecurity from '@vue/Section/Security';
import SectionAuthorize from '@vue/Section/Authorize';
import SectionFavorites from '@vue/Section/Favorites';
import Localisation from "@/js/Classes/Localisation";

function handleChunkLoadingError(e, module) {
    console.error(e);
    Messages.alert(['Unable to load {module}', {module}], 'Network error');
    throw e;
}

const SectionHelp = async () => {
    try {
        return await import(/* webpackChunkName: "HelpSection" */ '@vue/Section/Help');
    } catch(e) {
        handleChunkLoadingError(e, 'HelpSection');
    }
};

const SectionBackup = async () => {
    try {
        return await import(/* webpackChunkName: "BackupSection" */ '@vue/Section/Backup');
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
        return await import(/* webpackChunkName: "AppsSection" */ '@vue/Section/Apps');
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
            {name: 'Apps & Extensions', path: '/apps', components: {main: SectionApps}}
        ]
    }
);

export default router;