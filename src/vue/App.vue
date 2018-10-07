<template>
    <div id="app" class="passwords" :data-server-version="serverVersion">
        <div id="app-navigation">
            <ul>
                <router-link class="nav-icon-all" :to="{ name: 'All'}" active-class="active" :exact="true" tag="li">
                    <translate say="All"/>
                </router-link>
                <router-link class="nav-icon-folders" :to="{ name: 'Folders'}" active-class="active" tag="li">
                    <translate say="Folders"/>
                </router-link>
                <router-link class="nav-icon-recent" :to="{ name: 'Recent'}" active-class="active" tag="li">
                    <translate say="Recent"/>
                </router-link>
                <router-link class="nav-icon-favorites" :to="{ name: 'Favorites'}" active-class="active" tag="li">
                    <translate say="Favorites"/>
                </router-link>
                <router-link class="nav-icon-shared" :to="{ name: 'Shared'}" active-class="active" tag="li">
                    <translate say="Shared"/>
                </router-link>
                <router-link class="nav-icon-tags" :to="{ name: 'Tags'}" active-class="active" tag="li">
                    <translate say="Tags"/>
                </router-link>
                <router-link class="nav-icon-security" :to="{ name: 'Security'}" active-class="active" tag="li">
                    <translate say="Security"/>
                </router-link>
                <router-link class="nav-icon-search" :to="{ name: 'Search'}" active-class="active" tag="li" v-if="isSearchVisible">
                    <translate say="Search"/>
                </router-link>
            </ul>
            <ul id="app-settings" :class="{open: showMore}">
                <router-link class="nav-icon-trash" :to="{ name: 'Trash'}" active-class="active" tag="li">
                    <translate say="Trash"/>
                </router-link>
                <translate tag="li" class="nav-icon-more" @click="showMore = !showMore" say="More"/>
                <router-link class="nav-icon-settings" :to="{ name: 'Settings'}" active-class="active" tag="li">
                    <translate say="Settings"/>
                </router-link>
                <router-link class="nav-icon-backup" :to="{ name: 'Backup'}" active-class="active" tag="li">
                    <translate say="Backup and Restore"/>
                </router-link>
                <router-link class="nav-icon-help" :to="{ name: 'Help'}" active-class="active" tag="li">
                    <translate say="Handbook"/>
                </router-link>
                <translate tag="li" class="nav-icon-addon" @click="openBrowserAddonPage" say="Browser Extension"/>
            </ul>
        </div>

        <router-view name="main"/>
        <div id="app-popup">
            <div></div>
        </div>
        <star-chaser v-if="starChaser"/>
        <translate v-if="isBirthDay" icon="birthday-cake" id="birthday" @click="birthDayPopup"/>
    </div>
</template>

<script>
    import '@scss/app';
    import Translate from '@vc/Translate';
    import router from '@js/Helper/router';
    import Utility from '@js/Classes/Utility';
    import Messages from '@js/Classes/Messages';
    import SettingsManager from '@js/Manager/SettingsManager';

    export default {
        el        : '#main',
        router,
        components: {
            app          : {router},
            Translate,
            'star-chaser': () => import(/* webpackChunkName: "StarChaser" */ '@vue/Components/StarChaser')
        },

        data() {
            let serverVersion = SettingsManager.get('server.version'),
                showSearch    = SettingsManager.get('client.search.show');

            return {
                serverVersion,
                showSearch,
                showMore  : false,
                starChaser: false
            };
        },

        created() {
            SettingsManager.observe('client.search.show', (v) => { this.showSearch = v.value; });
        },

        computed: {
            isSearchVisible() {
                return this.$route.name === 'Search' || this.showSearch;
            },
            isBirthDay() {
                let today = new Date(),
                    bday  = new Date(`${today.getFullYear()}-01-12`);

                return bday.setHours(0, 0, 0, 0) === today.setHours(0, 0, 0, 0);
            }
        },

        methods: {
            openBrowserAddonPage() {
                if(navigator.userAgent.indexOf('Firefox') !== -1) {
                    Utility.openLink('https://addons.mozilla.org/firefox/addon/nextcloud-passwords');
                } else {
                    Utility.openLink('https://chrome.google.com/webstore/detail/nextcloud-passwords/mhajlicjhgoofheldnmollgbgjheenbi');
                }
            },
            birthDayPopup() {
                document.getElementById('birthday').remove();
                Messages.info('Today in 2018, the first version of passwords was published. Thank you for using the app.');
            }
        }
    };
</script>

<style lang="scss">
    #app-navigation {
        li {
            line-height   : 44px;
            padding       : 0 12px;
            white-space   : nowrap;
            text-overflow : ellipsis;
            color         : $color-grey-darker;
            cursor        : pointer;
            transition    : box-shadow .1s ease-in-out, color .1s ease-in-out;

            &:hover,
            &:active,
            &.active { color : $color-black; }

            &:before {
                font-family   : FontAwesome, sans-serif;
                font-size     : 1rem;
                padding-right : 10px;
                width         : 1rem;
                text-align    : center;
                display       : inline-block;
            }

            &.nav-icon-all:before { content : "\f0ac"; }
            &.nav-icon-folders:before { content : "\f07b"; }
            &.nav-icon-recent:before { content : "\f017"; }
            &.nav-icon-tags:before { content : "\f02c"; }
            &.nav-icon-security:before { content : "\f132"; }
            &.nav-icon-shared:before { content : "\f1e0"; }
            &.nav-icon-favorites:before { content : "\f005"; }
            &.nav-icon-search:before { content : "\f002"; }
            &.nav-icon-trash:before { content : "\f014"; }
            &.nav-icon-more:before { content : "\f067"; }
            &.nav-icon-settings:before { content : "\f013"; }
            &.nav-icon-addon:before { content : "\f12e"; }
            &.nav-icon-help:before { content : "\f059"; }
            &.nav-icon-backup:before { content : "\f187"; }

            span {
                cursor : pointer;
            }
        }

        #app-settings {
            position         : fixed;
            overflow         : hidden;
            max-height       : 88px;
            background-color : $color-white;
            border-right     : 1px solid $color-grey-lighter;
            transition       : max-height 0.25s ease-in-out;

            &.open {
                max-height : 264px;

                li.nav-icon-more:before { content : "\f068"; }
            }
        }
    }

    #birthday {
        position      : fixed;
        right         : 20px;
        bottom        : 20px;
        background    : $color-theme;
        color         : $color-white;
        line-height   : 40px;
        width         : 40px;
        text-align    : center;
        border-radius : 50%;
        cursor        : pointer;
        font-size     : 18px;
        opacity       : 0.5;
        transition    : opacity .25s ease-in-out;

        &:hover {
            opacity : 1;
        }
    }
</style>