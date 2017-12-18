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

Vue.use(Router);

export default new Router(
    {
        routes: [
            {name:"All", path: '*', components: {main: SectionAll}},
            {name:"Folders", path: '/show/folders/:folder?', components: {main: SectionFolders}},
            {name:"Tags", path: '/show/tags/:tag?', components: {main: SectionTags}},
            {name:"Recent", path: '/show/recent', components: {main: SectionRecent}},
            {name:"Favourites", path: '/show/favourites', components: {main: SectionFavourites}},
            {name:"Shared", path: '/show/shared', components: {main: SectionShared}},
            {name:"Security", path: '/show/security/:status?', components: {main: SectionSecurity}},
            {name:"Trash", path: '/show/trash', components: {main: SectionTrash}}
        ]
    }
);