import Vue from 'vue';
import Router from 'vue-router';
import SectionTags from '@vue/Section/Tags';
import SectionHelp from '@vue/Section/Help';
import SectionTrash from '@vue/Section/Trash';
import SectionBackup from '@vue/Section/Backup';
import SectionRecent from '@vue/Section/Recent';
import SectionShared from '@vue/Section/Shared';
import SectionFolders from '@vue/Section/Folders';
import SectionSecurity from '@vue/Section/Security';
import SectionSettings from '@vue/Section/Settings';
import SectionFavourites from '@vue/Section/Favourites';

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
            {name: 'Backup', path: '/backup/:action?', components: {main: SectionBackup}},
            {name: 'Settings', path: '/settings', components: {main: SectionSettings}},
            {name: 'Trash', path: '/trash', components: {main: SectionTrash}},
            {name: 'Help', path: '/help/:page?', components: {main: SectionHelp}}
        ],

        scrollBehavior (to, from, savedPosition) {
            if(!to.hash) return { x: 0, y: 0 };

            let $el = document.querySelector(`#app-content ${to.hash}`);
            if($el) {
                document.getElementById('app-content').scrollTop = $el.offsetTop - document.getElementById('controls').offsetHeight;
            }

            return { x: 0, y: 0 };
        }
    }
);

export default router;