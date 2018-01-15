<template>
    <div id="app" class="passwords">
        <div id="app-navigation">
            <ul>
                <router-link class="nav-icon-all" to="/" active-class="active" :exact="true" tag="li">
                    <translate>All</translate>
                </router-link>
                <router-link class="nav-icon-folders" to="/folders" active-class="active" tag="li">
                    <translate>Folders</translate>
                </router-link>
                <router-link class="nav-icon-recent" to="/recent" active-class="active" tag="li">
                    <translate>Recent</translate>
                </router-link>
                <router-link class="nav-icon-favourites" to="/favourites" active-class="active" tag="li">
                    <translate>Favourites</translate>
                </router-link>
                <router-link class="nav-icon-shared" to="/shared" active-class="active" tag="li">
                    <translate>Shared</translate>
                </router-link>
                <router-link class="nav-icon-tags" to="/tags" active-class="active" tag="li">
                    <translate>Tags</translate>
                </router-link>
                <router-link class="nav-icon-security" to="/security" active-class="active" tag="li">
                    <translate>Security</translate>
                </router-link>
                <router-link class="nav-icon-trash" to="/trash" active-class="active" tag="li">
                    <translate>Trash</translate>
                </router-link>
            </ul>
            <ul id="app-settings" :class="{open: showMore}">
                <translate tag="li" class="nav-icon-more" @click="showMore = !showMore">More</translate>
                <translate tag="li" class="nav-icon-addon" @click="openBrowserAddonPage">Browser Extension</translate>
                <!--<router-link class="nav-icon-backup" to="/backup" active-class="active" tag="li">
                    <translate>Backup</translate>
                </router-link>-->
            </ul>
        </div>

        <router-view name="main"/>
        <div id="app-popup">
            <div></div>
        </div>
    </div>
</template>

<script>
    import "@scss/app";
    import router from '@js/Helper/router';
    import Utility from "@js/Classes/Utility";
    import Translate from '@vc/Translate.vue';

    export default {
        el        : '#main',
        router,
        components: {
            app: {router},
            Translate
        },

        data() {
            return {
                showMore: false
            }
        },

        methods: {
            openBrowserAddonPage() {
                if(navigator.userAgent.indexOf('Firefox') !== -1) {
                    Utility.openLink('https://addons.mozilla.org/de/firefox/addon/nextcloud-passwords');
                } else {
                    Utility.openLink('https://github.com/marius-wieschollek/passwords-webextension/wiki/chromium-builds');
                }
            }
        }
    }
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

            &:hover,
            &:active,
            &.active { color : $color-black; }

            &:before {
                font-family   : FontAwesome, sans-serif;
                font-size     : 1rem;
                padding-right : 10px;
                position      : relative;
                bottom        : -2px;
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
            &.nav-icon-favourites:before { content : "\f005"; }
            &.nav-icon-trash:before { content : "\f014"; }
            &.nav-icon-more:before { content : "\f067"; }
            &.nav-icon-settings:before { content : "\f013"; }
            &.nav-icon-addon:before { content : "\f12e"; }
            &.nav-icon-backup:before { content : "\f187"; }

            span {
                cursor : pointer;
            }
        }

        #app-settings {
            position   : fixed;
            overflow   : hidden;
            max-height : 45px;
            transition : max-height 0.25s ease-in-out;

            &.open {
                max-height : 90px;

                li.nav-icon-more:before { content : "\f068"; }
            }
        }
    }
</style>