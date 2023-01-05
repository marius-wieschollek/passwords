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
                    <earth-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Folders')" :to="{ name: 'Folders'}">
                    <folder-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Recent')" :to="{ name: 'Recent'}">
                    <clock-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Favorites')" :to="{ name: 'Favorites'}">
                    <star-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item-shared/>
                <app-navigation-item :title="t('Tags')" :to="{ name: 'Tags'}">
                    <tag-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Security')" :to="{ name: 'Security'}" v-if="isSecurityVisible">
                    <shield-half-full-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Search')" :to="{ name: 'Search'}" v-if="isSearchVisible">
                    <magnify-icon slot="icon"/>
                </app-navigation-item>

                <session-timeout v-if="!isMobile"/>
                <app-navigation-item :title="t('Trash')" :pinned="true" :to="{ name: 'Trash'}" data-drop-type="trash" icon="icon-delete" />
            </template>

            <nc-app-navigation-settings slot="footer" :title="t('More')">
                <app-navigation-item :title="t('Settings')" :to="{ name: 'Settings'}">
                    <cog-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Backup and Restore')" :to="{ name: 'Backup'}">
                    <archive-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Handbook')" :to="{ name: 'Help'}">
                    <help-circle-icon slot="icon"/>
                </app-navigation-item>
                <app-navigation-item :title="t('Apps and Extensions')" :to="{ name: 'Apps and Extensions'}">
                    <puzzle-icon slot="icon"/>
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
        <session-timeout scope="global" v-if="isMobile"/>
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
    import SessionTimeout from '@vue/Components/SessionTimeout';
    import NcContent from '@nc/NcContent';
    import NcAppContent from '@nc/NcAppContent';
    import NcAppNavigation from '@nc/NcAppNavigation';
    import NcAppNavigationSettings from '@nc/NcAppNavigationSettings';
    import EarthIcon from "@icon/Earth";
    import FolderIcon from "@icon/Folder";
    import StarIcon from "@icon/Star";
    import TagIcon from "@icon/Tag";
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull";
    import MagnifyIcon from "@icon/Magnify";
    import PuzzleIcon from "@icon/Puzzle";
    import HelpCircleIcon from "@icon/HelpCircle";
    import ArchiveIcon from "@icon/Archive";
    import CogIcon from "@icon/Cog";
    import PasswordSidebar from "@vc/Sidebar/PasswordSidebar";
    import ClockIcon from "@icon/Clock";
    import Application from "@js/Init/Application";
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import AppNavigationItemShared from "@vue/AppNavigationItemShared.vue";

    export default {
        el        : '#content',
        router,
        components: {
            AppNavigationItemShared,
            AppNavigationItem,
            ClockIcon,
            PasswordSidebar,
            CogIcon,
            ArchiveIcon,
            HelpCircleIcon,
            PuzzleIcon,
            MagnifyIcon,
            ShieldHalfFullIcon,
            TagIcon,
            StarIcon,
            FolderIcon,
            EarthIcon,
            SessionTimeout,
            Translate,
            NcContent,
            NcAppContent,
            NcAppNavigation,
            NcAppNavigationSettings,
            'star-chaser': () => import(/* webpackChunkName: "StarChaser" */ '@vue/Components/StarChaser')
        },

        data() {
            let showSearch   = SettingsService.get('client.search.show'),
                showSecurity = SettingsService.get('user.password.security.hash') > 0;

            return {
                showSearch,
                showSecurity,
                showMore           : false,
                starChaser         : false,
                APP_MAIN_VERSION   : APP_MAIN_VERSION,
                APP_FEATURE_VERSION: APP_FEATURE_VERSION,
                isMobile           : Application.isMobile,
                sidebar            : null
            };
        },

        created() {
            SettingsService.observe('client.search.show', (v) => { this.showSearch = v.value; });

            router.afterEach(async (to, from) => {
                let moreRoutes = ['Settings', 'Backup', 'Help', 'Apps and Extensions'];

                if(moreRoutes.indexOf(from.name) === -1 && moreRoutes.indexOf(to.name) !== -1) {
                    return this.showMore = true;
                }
            });

            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth <= 768;
            });
        },

        computed: {
            isSearchVisible() {
                return this.$route.name === 'Search' || this.showSearch;
            },
            isSecurityVisible() {
                return this.$route.name === 'Security' || this.showSecurity;
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
    &.blocking {
        #app-navigation {
            z-index : 1000;
        }
    }
    
    @media all and (min-width: $width-1024-above) {
        button.app-navigation-toggle {
            display: none !important;
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