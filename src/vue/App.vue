<template>
    <nc-content app-name="passwords">
        <nc-app-navigation>
            <template id="app-passwords-navigation" #list>
                <nc-app-navigation-item :title="t('All')" :to="{ name: 'All'}" :exact="true">
                    <earth-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Folders')" :to="{ name: 'Folders'}">
                    <folder-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Recent')" :to="{ name: 'Recent'}">
                    <history-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Favorites')" :to="{ name: 'Favorites'}">
                    <star-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Shares')" :to="{ name: 'Shares'}">
                    <share-variant-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Tags')" :to="{ name: 'Tags'}">
                    <tag-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Security')" :to="{ name: 'Security'}" v-if="isSecurityVisible">
                    <shield-half-full-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Search')" :to="{ name: 'Search'}" v-if="isSearchVisible">
                    <magnify-icon slot="icon"/>
                </nc-app-navigation-item>

                <session-timeout v-if="!isMobile"/>
                <nc-app-navigation-item :title="t('Trash')" :pinned="true" :to="{ name: 'Trash'}" data-drop-type="trash">
                    <delete-icon slot="icon"/>
                </nc-app-navigation-item>
            </template>

            <nc-app-navigation-settings slot="footer" :title="t('More')">
                <nc-app-navigation-item :title="t('Settings')" :to="{ name: 'Settings'}">
                    <cog-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Backup and Restore')" :to="{ name: 'Backup'}">
                    <archive-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Handbook')" :to="{ name: 'Help'}">
                    <help-circle-icon slot="icon"/>
                </nc-app-navigation-item>
                <nc-app-navigation-item :title="t('Apps and Extensions')" :to="{ name: 'Apps and Extensions'}">
                    <puzzle-icon slot="icon"/>
                </nc-app-navigation-item>
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
    import NcAppNavigationItem from '@nc/NcAppNavigationItem';
    import NcAppNavigationNew from '@nc/NcAppNavigationNew';
    import NcAppNavigationSettings from '@nc/NcAppNavigationSettings';
    import NcAppSidebar from '@nc/NcAppSidebar';
    import DeleteIcon from "@icon/Delete.vue";
    import EarthIcon from "@icon/Earth.vue";
    import FolderIcon from "@icon/Folder.vue";
    import HistoryIcon from "@icon/History.vue";
    import StarIcon from "@icon/Star.vue";
    import TagIcon from "@icon/Tag.vue";
    import ShareVariantIcon from "@icon/ShareVariant.vue";
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull.vue";
    import MagnifyIcon from "@icon/Magnify.vue";
    import PuzzleIcon from "@icon/Puzzle.vue";
    import HelpCircleIcon from "@icon/HelpCircle.vue";
    import ArchiveIcon from "@icon/Archive.vue";
    import CogIcon from "@icon/Cog.vue";
    import PasswordSidebar from "@vc/Sidebar/PasswordSidebar.vue";

    export default {
        el        : '#content',
        router,
        components: {
            PasswordSidebar,
            CogIcon,
            ArchiveIcon,
            HelpCircleIcon,
            PuzzleIcon,
            MagnifyIcon,
            ShieldHalfFullIcon,
            ShareVariantIcon,
            TagIcon,
            StarIcon,
            HistoryIcon,
            FolderIcon,
            EarthIcon,
            DeleteIcon,
            SessionTimeout,
            Translate,
            NcContent,
            NcAppContent,
            NcAppNavigation,
            NcAppNavigationItem,
            NcAppNavigationNew,
            NcAppNavigationSettings,
            NcAppSidebar,
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
                isMobile           : window.innerWidth <= 768,
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
#app {
    width   : 100%;
    display : flex;

    &.blocking {
        #app-content {
            position  : static;
            transform : none;

            .app-content-left {
                transition : none;
                transform  : none;
            }
        }

        #app-navigation {
            z-index : 1000;
        }
    }

    @media(max-width : $width-small) {
        #app-content {
            margin-right : 0;
            width        : 100%;
            transition   : width 300ms, margin-left 300ms;
        }

        &.mobile-open {
            #app-navigation {
                transform : translateX(0);
                z-index   : 1001;
            }

            #app-content {
                background-color : var(--color-main-background);
                border-left      : 1px solid var(--color-border);
                width            : calc(100% - 299px);
                margin-left      : 299px;

                .item-list .row .date {
                    display : none;
                }
            }
        }
    }

    @media(max-width : $width-extra-small) {
        &.mobile-open #app-content {
            width       : 360px;
            margin-left : 299px;
        }
    }

    @media (min-width : $width-small) and (max-width : $width-medium) {
        #app-content {
            transition : margin-left 0.25s ease-in-out;

            &.show-details {
                .app-content-left {
                    width : calc(100% - 360px);
                }

                .app-content-right {
                    width : 360px;
                }
            }
        }

        &.mobile-open {
            #app-content {
                margin-left : 300px;
            }
        }
    }
}

#app-navigation {
    transition : transform 300ms;

    ul {
        li {
            line-height   : 44px;
            white-space   : nowrap;
            text-overflow : ellipsis;
            color         : var(--color-main-text);

            i {
                font-size  : 1rem;
                width      : 1rem;
                box-sizing : content-box !important;
                padding    : 0 10px 0 16px;
            }

            a {
                cursor     : pointer;
                transition : box-shadow .1s ease-in-out, opacity .1s ease-in-out;

                i {
                    margin-left : -1rem;
                }
            }
        }

        &.menu-main {
            height : 100%;
        }

        &.menu-secondary {
            height      : auto;
            flex-shrink : 0;
        }

        &#app-settings {
            position         : relative;
            overflow         : hidden;
            max-height       : 60px;
            height           : auto;
            z-index          : 100;
            border-right     : 1px solid var(--color-border);
            transition       : max-height 0.25s ease-in-out;
            background-color : transparent;
            padding-bottom   : 4px;

            .more {

                a,
                a.active {
                    box-shadow : none;
                }
            }

            &.open {
                max-height  : 288px;
                flex-shrink : 0;

                @media (max-height : 360px) {
                    position : fixed;
                    bottom   : 0;
                }
            }

            &:not(.open) {
                li:nth-child(2) {
                    opacity : 0;
                }
            }
        }
    }

    @media(min-width : $width-small) {
        z-index : 1001;
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