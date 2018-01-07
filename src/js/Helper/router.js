import Vue from 'vue';
import Router from 'vue-router';
import SectionAll from '@vue/Section/All.vue';
import SectionFolders from '@vue/Section/Folders.vue';
import SectionTags from '@vue/Section/Tags.vue';
import SectionRecent from '@vue/Section/Recent.vue';
import SectionFavourites from '@vue/Section/Favourites.vue';
import SectionShared from '@vue/Section/Shared.vue';
import SectionSecurity from '@vue/Section/Security.vue';
import SectionTrash from '@vue/Section/Trash.vue';
import SectionBackup from '@vue/Section/Backup.vue';

Vue.use(Router);

export default new Router(
    {
        routes: [
            {name: "All", path: '*', components: {main: SectionAll}},
            {name: "Folders", path: '/folders/:folder?', components: {main: SectionFolders}},
            {name: "Tags", path: '/tags/:tag?', components: {main: SectionTags}},
            {name: "Recent", path: '/recent', components: {main: SectionRecent}},
            {name: "Favourites", path: '/favourites', components: {main: SectionFavourites}},
            {name: "Shared", path: '/shared/:type?', components: {main: SectionShared}},
            {name: "Security", path: '/security/:status?', components: {main: SectionSecurity}},
            {name: "Backup", path: '/backup', components: {main: SectionBackup}},
            {name: "Trash", path: '/trash', components: {main: SectionTrash}}
        ]
    }
);