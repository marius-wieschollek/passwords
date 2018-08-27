<template>
    <div id="controls">
        <div id="app-navigation-toggle" class="icon-menu" @click="showNavigation()"></div>
        <div class="breadcrumb">
            <div class="crumb svg" data-dir="/">
                <router-link :to="getBaseRoute"><img class="svg" :src="getHomeIcon" alt="Home"></router-link>
            </div>
            <div class="crumb svg" v-for="(item, index) in getItems" :class="{first:index===0,current:index === getItems.length - 1}">
                <router-link :to="item.path" :data-folder-id="item.folderId" :data-drop-type="item.dropType">{{ item.label }}</router-link>
            </div>
            <div class="crumb svg crumbmenu" :class="{active: showCrumbMenu}" v-if="getCrumbMenuItems.length !== 0">
                <span class="icon icon-more" @click="toggleCrumbMenu"></span>
                <div class="popovermenu menu menu-center" @click="toggleCrumbMenu">
                    <ul>
                        <li v-for="item in getCrumbMenuItems" class="crumblist">
                            <router-link :to="item.path" :data-folder-id="item.folderId" :data-drop-type="item.dropType">
                                <span :class="getCrumbItemIcon"></span>
                                {{ item.label }}
                            </router-link>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="passwords-more-menu actions" v-if="showAddNew" :class="{active: showMoreMenu}">
                <span class="button new" @click="toggleMoreMenu"><span class="icon icon-add"></span></span>
                <div class="popovermenu menu menu-center" @click="toggleMoreMenu">
                    <ul>
                        <li>
                        <span class="menuitem" v-if="newFolder" @click="createFolder">
                            <span class="icon icon-folder svg"></span>
                            <translate class="displayname" say="New Folder"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="newTag" @click="createTag">
                            <span class="icon icon-tag svg"></span>
                            <translate class="displayname" say="New Tag"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="newPassword" @click="createPassword()">
                            <span class="icon icon-filetype-text svg"></span>
                            <translate class="displayname" say="New Password"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="restoreAll" @click="restoreAllEvent">
                            <span class="icon icon-history svg"></span>
                            <translate class="displayname" say="Restore All Items"/>
                        </span>
                        </li>
                        <li>
                        <span class="menuitem" v-if="deleteAll" @click="deleteAllEvent">
                            <span class="icon icon-delete svg"></span>
                            <translate class="displayname" say="Delete All Items"/>
                        </span>
                        </li>
                    </ul>
                </div>
            </div>
            <slot></slot>
        </div>
    </div>
</template>

<script>
    import $ from "jquery";
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import TagManager from '@js/Manager/TagManager';
    import Localisation from '@js/Classes/Localisation';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
            Translate
        },

        props: {
            newPassword: {
                type     : Boolean,
                'default': true
            },
            newFolder  : {
                type     : Boolean,
                'default': false
            },
            newTag     : {
                type     : Boolean,
                'default': false
            },
            deleteAll  : {
                type     : Boolean,
                'default': false
            },
            restoreAll : {
                type     : Boolean,
                'default': false
            },
            showAddNew : {
                type     : Boolean,
                'default': true
            },
            items      : {
                type     : Array,
                'default': () => { return []; }
            },
            folder     : {
                type     : String,
                'default': null
            },
            tag        : {
                type     : String,
                'default': null
            }
        },
        data() {
            return {
                showMoreMenu : false,
                showCrumbMenu: false
            };
        },

        computed: {
            getHomeIcon() {
                return API.baseUrl + 'core/img/places/home.svg';
            },
            getBaseRoute() {
                let route = this.$route.path;

                return route.substr(0, route.indexOf('/', 1));
            },
            getItems() {
                if(this.items.length === 0) {
                    return [
                        {path: this.$route.path, label: Localisation.translate(this.$route.name)}
                    ];
                }

                return this.items;
            },
            getCrumbMenuItems() {
                let items = this.getItems;
                if(items.length > 1) return items.slice(1);
                return [];
            },
            getCrumbItemIcon() {
                if(this.$route.name === 'Folders') return 'icon icon-folder';
                if(this.$route.name === 'Tags') return 'icon icon-tag';
                if(this.$route.name === 'Shared') return 'icon icon-shared';
                if(this.$route.name === 'Security') return 'fa fa-shield';
                if(this.$route.name === 'Trash') return 'fa fa-trash';
                if(this.$route.name === 'Help') return 'fa fa-book';
                if(this.$route.name === 'Backup') return 'icon icon-category-app-bundles';

                return 'icon icon-menu';
            }
        },

        methods: {
            toggleMoreMenu() {
                this.showMoreMenu = !this.showMoreMenu;
                this.showMoreMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
                if(this.showMoreMenu && this.showCrumbMenu) this.showCrumbMenu = false;
            },
            toggleCrumbMenu() {
                this.showCrumbMenu = !this.showCrumbMenu;
                this.showCrumbMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
                if(this.showCrumbMenu && this.showMoreMenu) this.showMoreMenu = false;
            },
            menuEvent($e) {
                if($($e.target).closest('.passwords-more-menu, .crumbmenu').length !== 0) return;
                this.showCrumbMenu = false;
                this.showMoreMenu = false;
                $(document).off('click', this.menuEvent);
            },
            createFolder() {
                FolderManager.createFolder(this.folder);
            },
            createTag() {
                TagManager.createTag();
            },
            createPassword() {
                PasswordManager.createPassword(this.folder, this.tag);
            },
            showNavigation() {
                $('#app-content').toggleClass('mobile-open');
            },
            deleteAllEvent() {
                this.$emit('deleteAll');
            },
            restoreAllEvent() {
                this.$emit('restoreAll');
            }
        }
    };
</script>

<style lang="scss">
    #controls {
        position : sticky;

        .crumbmenu,
        .passwords-more-menu {
            position : relative;
            order    : 2;

            &.passwords-more-menu {
                margin-left : 10px;
                display     : inline-block;
            }

            &.crumbmenu {
                display : none;

                .icon-more {
                    cursor : pointer;
                }

                .fa {
                    padding   : 10px;
                    font-size : 1rem;
                }
            }

            .menu {
                max-height : 0;
                margin     : 0;
                overflow   : hidden;
                transition : max-height 0.25s ease-in-out;
                display    : block;
            }

            &:not(.active) .menu {
                filter: none;
            }

            &.active .menu {
                overflow   : visible;
                max-height : 75px;
                animation  : 0.25s delay-overflow;
            }
        }

        .breadcrumb {
            display : flex;

            .crumb {
                white-space : nowrap;

                &.current {
                    font-weight : 600;
                }
            }
        }

        #app-navigation-toggle {
            display : none !important;
        }

        @keyframes delay-overflow {
            0% { overflow : hidden; }
            99% { overflow : hidden; }
            100% { overflow : visible; }
        }

        @media(max-width : $tablet-width) {
            padding-left : 0 !important;

            .breadcrumb {
                .crumb:not(.first):not(.crumbmenu) {
                    display : none;
                }
                .crumbmenu {
                    display : inline-flex;
                }
            }

            #app-navigation-toggle {
                display          : block !important;
                position         : sticky;
                min-width        : 44px;
                top              : 0;
                background-color : $color-white;
                opacity          : 1;
                color            : transparentize($color-black, 0.4);
                z-index          : 1;

                &:hover {
                    color : $color-black
                }
            }
        }
    }

    .edge {
        .popovermenu,
        #app-navigation .app-navigation-entry-menu {
            border : none !important;

            &:after {
                border : none !important;
            }
        }
    }
</style>