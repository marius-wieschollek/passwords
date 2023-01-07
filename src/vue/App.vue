<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-content app-name="passwords" :data-passwords-main-version="APP_MAIN_VERSION" :data-passwords-version="APP_FEATURE_VERSION">
        <nc-app-navigation open>
            <template id="app-passwords-navigation" #list>
                <app-navigation-item :title="t('All')" :to="{ name: 'All'}" :exact="true">
                    <earth-icon :size=20 slot="icon"/>
                </app-navigation-item>
                <app-navigation-item-folders/>
                <app-navigation-item :title="t('Recent')" :to="{ name: 'Recent'}">
                    <clock-icon :size=20 slot="icon"/>
                </app-navigation-item>
                <app-navigation-item-favorites/>
                <app-navigation-item-shared/>
                <app-navigation-item-tags/>
                <app-navigation-item-security/>
                <app-navigation-item :title="t('Search')" :to="{ name: 'Search'}" v-if="isSearchVisible">
                    <magnify-icon :size=20 slot="icon"/>
                </app-navigation-item>

                <session-timeout v-if="!isMobile && hasTimeout"/>
                <app-navigation-item :title="t('Trash')" :pinned="true" :to="{ name: 'Trash'}" data-drop-type="trash" icon="icon-delete"/>
            </template>

            <nc-app-navigation-settings slot="footer" :title="t('More')">
                <app-navigation-item :title="t('Settings')" :to="{ name: 'Settings'}">
                    <cog-icon :size=20 slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Backup and Restore')" :to="{ name: 'Backup'}">
                    <archive-icon :size=20 slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Handbook')" :to="{ name: 'Help'}">
                    <help-circle-icon :size=20 slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Apps and Extensions')" :to="{ name: 'Apps and Extensions'}">
                    <puzzle-icon :size=20 slot="icon"/>
                </app-navigation-item>
            </nc-app-navigation-settings>
        </nc-app-navigation>

        <nc-app-content>
            <router-view name="main"/>
        </nc-app-content>

        <password-sidebar v-if="sidebar && sidebar.type === 'password'" :sidebar="sidebar"/>

        <div id="app-popup">
            <div></div>
        </div>
        <session-timeout scope="global" v-if="isMobile && hasTimeout"/>
        <star-chaser v-if="starChaser"/>
        <translate v-if="isBirthDay" icon="birthday-cake" id="birthday" @click="birthDayPopup"/>
    </nc-content>
</template>

<script>
    import '@scss/app';
    import Translate from '@vc/Translate';
    import router from '@js/Helper/router';
    import Messages from '@js/Classes/Messages';
    import SettingsService from '@js/Services/SettingsService';
    import NcContent from '@nc/NcContent';
    import NcAppContent from '@nc/NcAppContent';
    import NcAppNavigation from '@nc/NcAppNavigation';
    import NcAppNavigationSettings from '@nc/NcAppNavigationSettings';
    import EarthIcon from "@icon/Earth";
    import MagnifyIcon from "@icon/Magnify";
    import PuzzleIcon from "@icon/Puzzle";
    import HelpCircleIcon from "@icon/HelpCircle";
    import ArchiveIcon from "@icon/Archive";
    import CogIcon from "@icon/Cog";
    import ClockIcon from "@icon/Clock";
    import Application from "@js/Init/Application";
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import AppNavigationItemShared from "@vc/Navigation/AppNavigationItemShared";
    import AppNavigationItemSecurity from "@vc/Navigation/AppNavigationItemSecurity";
    import AppNavigationItemTags from "@vc/Navigation/AppNavigationItemTags";
    import AppNavigationItemFolders from "@vc/Navigation/AppNavigationItemFolders";
    import AppNavigationItemFavorites from "@vc/Navigation/AppNavigationItemFavorites";
    import KeepAliveManager from "@js/Manager/KeepAliveManager";

    export default {
        el        : '#content',
        router,
        components: {
            AppNavigationItemFavorites,
            AppNavigationItemFolders,
            AppNavigationItemTags,
            AppNavigationItemSecurity,
            AppNavigationItemShared,
            AppNavigationItem,
            ClockIcon,
            CogIcon,
            ArchiveIcon,
            HelpCircleIcon,
            PuzzleIcon,
            MagnifyIcon,
            EarthIcon,
            Translate,
            NcContent,
            NcAppContent,
            NcAppNavigation,
            NcAppNavigationSettings,
            'session-timeout' : () => import(/* webpackChunkName: "SessionTimeout" */ '@vc/SessionTimeout'),
            'password-sidebar': () => import(/* webpackChunkName: "PasswordSidebar" */ '@vc/Sidebar/PasswordSidebar'),
            'star-chaser'     : () => import(/* webpackChunkName: "StarChaser" */ '@vue/Components/StarChaser')
        },

        data() {
            let showSearch = SettingsService.get('client.search.show');

            return {
                showSearch,
                starChaser         : false,
                APP_MAIN_VERSION   : APP_MAIN_VERSION,
                APP_FEATURE_VERSION: APP_FEATURE_VERSION,
                isMobile           : Application.isMobile,
                hasTimeout         : KeepAliveManager.hasTimeout,
                sidebar            : null
            };
        },

        created() {
            SettingsService.observe('client.search.show', (v) => { this.showSearch = v.value; });

            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth <= 768;
            });
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
            birthDayPopup() {
                document.getElementById('birthday').remove();
                Messages.info('Today in 2018, the first version of passwords was published. Thank you for using the app.');
            }
        }
    };
</script>

<style lang="scss">
#content-vue {
    .app-navigation-entry__children {
        margin-top : .25rem;
    }

    @media all and (min-width : $width-1024-above) {
        button.app-navigation-toggle {
            display : none !important;
        }
    }
}

#birthday {
    position      : fixed;
    right         : 20px;
    bottom        : 20px;
    background    : var(--color-primary);
    color         : var(--color-primary-text);
    line-height   : 40px;
    width         : 40px;
    text-align    : center;
    border-radius : 50%;
    cursor        : pointer;
    font-size     : 18px;
    z-index       : 1000;
    opacity       : 0.5;
    transition    : opacity .25s ease-in-out;

    &:hover {
        opacity : 1;
    }
}
</style>