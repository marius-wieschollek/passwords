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
        <app-navigation :has-timeout="hasTimeout" :is-mobile="isMobile" :is-search-visible="isSearchVisible" />

        <nc-app-content>
            <router-view name="main" />
        </nc-app-content>

        <password-sidebar v-if="sidebar && sidebar.type === 'password'" :sidebar="sidebar" />

        <div id="app-popup">
            <div></div>
        </div>
        <session-timeout scope="global" v-if="isMobile && hasTimeout" />
        <star-chaser v-if="starChaser" />
        <translate v-if="isBirthDay" icon="birthday-cake" id="birthday" @click="birthDayPopup" />
    </nc-content>
</template>

<script>
    import '@scss/app';
    import Translate            from '@vc/Translate';
    import router               from '@js/Helper/router';
    import Messages             from '@js/Classes/Messages';
    import SettingsService      from '@js/Services/SettingsService';
    import NcContent            from '@nc/NcContent';
    import NcAppContent         from '@nc/NcAppContent';
    import AppNavigationLoading from '@vc/Navigation/AppNavigationLoading';
    import Application          from '@js/Init/Application';
    import KeepAliveManager     from '@js/Manager/KeepAliveManager';

    export default {
        el        : '#content',
        router,
        components: {
            Translate,
            NcContent,
            NcAppContent,
            'app-navigation'  : () => ({
                component: import(/* webpackChunkName: "AppNavigation" */ '@vc/Navigation/AppNavigation'),
                loading  : AppNavigationLoading,
                delay    : 0
            }),
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

    &.blocking {
        z-index: 2001;

        #app-content-vue {
            z-index: 1800;
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