<template>
    <div id="app" class="passwords" :data-server-version="serverVersion">
        <div id="app-navigation">
            <ul>
                <router-link class="nav-icon-all" to="/" active-class="active" :exact="true" tag="li">
                    <translate say="All"/>
                </router-link>
                <router-link class="nav-icon-folders" to="/folders" active-class="active" tag="li">
                    <translate say="Folders"/>
                </router-link>
                <router-link class="nav-icon-recent" to="/recent" active-class="active" tag="li">
                    <translate say="Recent"/>
                </router-link>
                <router-link class="nav-icon-favourites" to="/favourites" active-class="active" tag="li">
                    <translate say="Favourites"/>
                </router-link>
                <router-link class="nav-icon-shared" to="/shared" active-class="active" tag="li">
                    <translate say="Shared"/>
                </router-link>
                <router-link class="nav-icon-tags" to="/tags" active-class="active" tag="li">
                    <translate say="Tags"/>
                </router-link>
                <router-link class="nav-icon-security" to="/security" active-class="active" tag="li">
                    <translate say="Security"/>
                </router-link>
            </ul>
            <ul id="app-settings" :class="{open: showMore}">
                <router-link class="nav-icon-trash" to="/trash" active-class="active" tag="li">
                    <translate say="Trash"/>
                </router-link>
                <translate tag="li" class="nav-icon-more" @click="showMore = !showMore" say="More"/>
                <router-link class="nav-icon-settings" to="/settings" active-class="active" tag="li">
                    <translate say="Settings"/>
                </router-link>
                <router-link class="nav-icon-backup" to="/backup" active-class="active" tag="li">
                    <translate say="Backup and Restore"/>
                </router-link>
                <translate tag="li" class="nav-icon-help" @click="openWikiPage" say="Handbook"/>
                <translate tag="li" class="nav-icon-addon" @click="openBrowserAddonPage" say="Browser Extension"/>
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
    import Translate from '@vc/Translate';
    import router from '@js/Helper/router';
    import Utility from "@js/Classes/Utility";

    export default {
        el        : '#main',
        router,
        components: {
            app: {router},
            Translate
        },

        data() {
            let serverVersion = document.querySelector('[data-constant="serverVersion"]').getAttribute('data-value');

            return {
                serverVersion: serverVersion,
                showMore     : false
            }
        },

        methods: {
            openBrowserAddonPage() {
                if(navigator.userAgent.indexOf('Firefox') !== -1) {
                    Utility.openLink('https://addons.mozilla.org/firefox/addon/nextcloud-passwords');
                } else {
                    Utility.openLink('https://github.com/marius-wieschollek/passwords-webextension/wiki/chromium-builds');
                }
            },
            openWikiPage() {
                Utility.openLink('https://git.mdns.eu/nextcloud/passwords/wikis/home#users');
            }
        }
    }
</script>

<style lang="scss">
    [data-constant] {
        display    : none;
        visibility : hidden;
    }

    .oc-dialog-dim {
        z-index: 1000;
    }

    form.searchbox {
        transition: opacity .25s ease-in-out;
    }

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
</style>