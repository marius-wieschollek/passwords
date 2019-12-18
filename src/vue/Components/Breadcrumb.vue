<template>
    <div id="controls">
        <div id="app-navigation-toggle" class="icon-menu" @click="showNavigation()"></div>
        <div class="breadcrumb">
            <div class="crumb svg" data-dir="/">
                <router-link :to="getBaseRoute" class="home">&nbsp;</router-link>
            </div>
            <div class="crumb svg"
                 v-for="(item, index) in getItems"
                 :class="{first:index===0,current:index === getItems.length - 1}">
                <router-link :to="item.path" :data-folder-id="item.folderId" :data-drop-type="item.dropType">{{
                                                                                                             item.label
                                                                                                             }}
                </router-link>
            </div>
            <div class="crumb svg crumbmenu" :class="{active: showCrumbMenu}" v-if="getCrumbMenuItems.length !== 0">
                <span class="icon icon-more" @click="toggleCrumbMenu"></span>
                <div class="popovermenu menu menu-center" @click="toggleCrumbMenu" :style="getCrumbMenuStyle">
                    <ul>
                        <li v-for="item in getCrumbMenuItems" class="crumblist">
                            <router-link :to="item.path"
                                         :data-folder-id="item.folderId"
                                         :data-drop-type="item.dropType"
                                         :title="item.label">
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
            getCrumbMenuStyle() {
                let height = '0px';
                if(this.showCrumbMenu) {
                    height = `${this.getCrumbMenuItems.length * 44}px`;
                }
                return {height};
            },
            getCrumbItemIcon() {
                if(this.$route.name === 'Folders') return 'icon icon-folder';
                if(this.$route.name === 'Tags') return 'icon icon-tag';
                if(this.$route.name === 'Shares') return 'icon icon-shared';
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
                let appClasses = document.getElementById('app').classList;
                if(!appClasses.contains('mobile-open') && this.$parent.detail) {
                    this.$parent.detail.type = 'none';
                }
                appClasses.toggle('mobile-open');
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
        top      : 50px;
        width    : 100%;

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
                    cursor  : pointer;
                    padding : 12px 1rem;
                }

                .fa {
                    padding   : 10px;
                    font-size : 1rem;
                    margin    : 0;
                }

                .menu {
                    left       : auto;
                    right      : 58%;
                    max-height : 90px;
                }
            }

            .menu {
                max-height : 0;
                margin     : 0;
                overflow   : hidden;
                transition : max-height 0.25s ease-in-out;
                display    : block;
                position   : relative;
                left       : -134px;

                ul {
                    padding-right : 0;
                }

                a {
                    overflow      : hidden;
                    white-space   : nowrap;
                    text-overflow : ellipsis;
                    display       : block;
                }
            }

            &:not(.active) .menu {
                filter       : none;
                border-color : transparent;
            }

            &.active .menu {
                overflow   : visible;
                max-height : 90px;
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

                .home {
                    background : var(--icon-home-000) no-repeat center;
                    width      : 40px;
                }
            }
        }

        #app-navigation-toggle {
            display : none !important;

            @media(max-width : $width-medium) {
                display   : block !important;
            }
        }

        @keyframes delay-overflow {
            0% { overflow : hidden; }
            99% { overflow : hidden; }
            100% { overflow : visible; }
        }

        @media(max-width : $width-small) {
            padding-left : 0 !important;

            .breadcrumb {
                .crumb:not(.first):not(.crumbmenu) {
                    display : none;
                }

                .crumbmenu {
                    background-image : none;
                    display          : inline-flex;

                    .menu.menu-center {
                        position : absolute;
                    }
                }
            }

            #app-navigation-toggle {
                display   : block !important;
                position  : static;
                min-width : 44px;
                top       : 0;
                opacity   : 1;
                z-index   : 1;
            }
        }

        @media(max-width : $width-extra-small) {
            .crumbmenu {
                &.active .menu.menu-center {
                    z-index : 111;
                }
            }
        }
    }
</style>