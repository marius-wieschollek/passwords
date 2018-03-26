import Vue from 'vue';
import Router from 'vue-router';
import Messages from '@js/Classes/Messages';
import SectionTags from '@vue/Section/Tags';
import SectionTrash from '@vue/Section/Trash';
import SectionRecent from '@vue/Section/Recent';
import SectionShared from '@vue/Section/Shared';
import SectionFolders from '@vue/Section/Folders';
import SectionSecurity from '@vue/Section/Security';
import SectionFavourites from '@vue/Section/Favourites';

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
        return await import(/* webpackChunkName: "SettingsSection" */ '@vue/Section/Settings');
    } catch(e) {
        handleChunkLoadingError(e, 'SettingsSection');
    }
};

Vue.use(Router);
let router = new Router(
    {
        routes: [
            {name: 'Folders', path: '/folders/:folder?', components: {main: SectionFolders}},
            {name: 'Tags', path: '/tags/:tag?', components: {main: SectionTags}},
            {name: 'Recent', path: '/recent', components: {main: SectionRecent}},
            {name: 'Favourites', path: '/favourites', components: {main: SectionFavourites}},
            {name: 'Shared', path: '/shared/:type?', components: {main: SectionShared}},
            {name: 'Security', path: '/security/:status?', components: {main: SectionSecurity}},
            {name: 'Trash', path: '/trash', components: {main: SectionTrash}},
            {name: 'Settings', path: '/settings', components: {main: SectionSettings}},
            {name: 'Backup', path: '/backup/:action?', components: {main: SectionBackup}},
            {name: 'Help', path: '/help/:page?', components: {main: SectionHelp}}
        ]
    }
);

export default router;