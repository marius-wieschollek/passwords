<template>
    <div id="app" class="passwords" :data-server-version="serverVersion">
        <div id="app-navigation">
            <ul class="menu-main">
                <li>
                    <router-link :to="{ name: 'All'}" active-class="active" :exact="true">
                        <translate say="All" icon="globe"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Folders'}" active-class="active">
                        <translate say="Folders" icon="folder"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Recent'}" active-class="active">
                        <translate say="Recent" icon="clock-o"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Favorites'}" active-class="active">
                        <translate say="Favorites" icon="star"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Shares'}" active-class="active">
                        <translate say="Shares" icon="share-alt"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Tags'}" active-class="active">
                        <translate say="Tags" icon="tag"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Security'}" active-class="active">
                        <translate say="Security" icon="shield"/>
                    </router-link>
                </li>
                <li v-if="isSearchVisible">
                    <router-link :to="{ name: 'Search'}"
                                 active-class="active">
                        <translate say="Search" icon="search"/>
                    </router-link>
                </li>
            </ul>
            <ul class="menu-secondary">
                <session-timeout v-if="!isMobile"/>
                <li>
                    <router-link :to="{ name: 'Trash'}" active-class="active">
                        <translate say="Trash" icon="trash"/>
                    </router-link>
                </li>
            </ul>
            <ul id="app-settings" :class="{open: showMore}">
                <li class="more">
                    <translate @click="showMore = !showMore" say="More" :icon="showMore ? 'minus':'plus'" :class="{active:showMore}" tag="a"/>
                </li>
                <li>
                    <router-link :to="{ name: 'Settings'}" active-class="active">
                        <translate say="Settings" icon="cog"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Backup'}" active-class="active">
                        <translate say="Backup and Restore" icon="archive"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Help'}" active-class="active">
                        <translate say="Handbook" icon="question-circle"/>
                    </router-link>
                </li>
                <li>
                    <router-link :to="{ name: 'Apps & Extensions'}" active-class="active">
                        <translate say="Apps & Extensions" icon="puzzle-piece"/>
                    </router-link>
                </li>
            </ul>
        </div>

        <router-view name="main"/>
        <div id="app-popup">
            <div></div>
        </div>
        <session-timeout scope="global" v-if="isMobile"/>
        <star-chaser v-if="starChaser"/>
        <translate v-if="isBirthDay" icon="birthday-cake" id="birthday" @click="birthDayPopup"/>
    </div>
</template>

<script>
    import '@scss/app';
    import Translate from '@vc/Translate';
    import router from '@js/Helper/router';
    import Messages from '@js/Classes/Messages';
    import SettingsService from '@js/Services/SettingsService';
    import SessionTimeout from '@vue/Components/SessionTimeout';

    export default {
        el        : '#main',
        router,
        components: {
            SessionTimeout,
            Translate,
            'star-chaser': () => import(/* webpackChunkName: "StarChaser" */ '@vue/Components/StarChaser')
        },

        data() {
            let serverVersion = SettingsService.get('server.version'),
                showSearch    = SettingsService.get('client.search.show');

            return {
                serverVersion,
                showSearch,
                showMore  : false,
                starChaser: false,
                isMobile  : window.innerWidth <= 768
            };
        },

        created() {
            SettingsService.observe('client.search.show', (v) => { this.showSearch = v.value; });

            router.afterEach(async (to, from) => {
                let moreRoutes = ['Settings', 'Backup', 'Help', 'Apps & Extensions'];

                if(moreRoutes.indexOf(from.name) === -1 && moreRoutes.indexOf(to.name) !== -1) {
                    return this.showMore = true;
                }
            });

            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth <= 768;
            })
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
    #app {
        width : 100%;

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

        @media (min-width: $width-small) and (max-width : $width-medium) {
            #app-content {
                transition: margin-left 0.25s ease-in-out;

                &.show-details {
                    .app-content-left {
                        width: calc(100% - 360px);
                    }
                    .app-content-right {
                        width: 360px;
                    }
                }
            }

            &.mobile-open {
                #app-content {
                    margin-left: 300px;
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
                        margin-left : -44px;
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
                max-height       : 44px;
                height           : auto;
                z-index          : 100;
                background-color : var(--color-main-background);
                border-right     : 1px solid var(--color-border);
                transition       : max-height 0.25s ease-in-out;

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