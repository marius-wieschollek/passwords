import Vue from 'vue';
import Router from 'vue-router';
import SectionAll from '@vc/Section/All.vue';
import SectionFolders from '@vc/Section/Folders.vue';
import SectionTags from '@vc/Section/Tags.vue';
import SectionRecent from '@vc/Section/Recent.vue';
import SectionFavourites from '@vc/Section/Favourites.vue';
import SectionShared from '@vc/Section/Shared.vue';
import SectionSecurity from '@vc/Section/Security.vue';
import SectionTrash from '@vc/Section/Trash.vue';

Vue.use(Router);

export default new Router(
    {
        routes: [
            {path: '*', components: {main: SectionAll}},
            {path: '/show/all', components: {main: SectionAll}},
            {path: '/show/folders', components: {main: SectionFolders}},
            {path: '/show/tags', components: {main: SectionTags}},
            {path: '/show/recent', components: {main: SectionRecent}},
            {path: '/show/favourites', components: {main: SectionFavourites}},
            {path: '/show/shared', components: {main: SectionShared}},
            {path: '/show/security', components: {main: SectionSecurity}},
            {path: '/show/trash', components: {main: SectionTrash}}
        ]
    }
);