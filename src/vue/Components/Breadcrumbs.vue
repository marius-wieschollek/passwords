<template>
    <div id="controls">
        <div id="app-navigation-toggle" class="icon-menu" @click="showNavigation()"></div>
        <div class="breadcrumb">
            <div class="crumb svg" data-dir="/">
                <a href="#"><img class="svg" :src="getHomeIcon" alt="Home"></a>
            </div>
            <div class="crumb svg" v-for="(item, index) in getItems" :class="{current:index === getItems.length - 1}">
                <router-link :to="item.path" :data-folder-id="item.folderId" :data-drop-type="item.dropType">{{ item.label }}</router-link>
            </div>
            <div class="actions creatable" v-if="showAddNew" :class="{active: showMenu}">
                <span class="button new" @click="toggleMenu()"><span class="icon icon-add"></span></span>
                <div class="newPasswordMenu popovermenu bubble menu menu-left open" @click="toggleMenu()">
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
                        <span class="menuitem" v-if="deleteAll" @click="deleteAllEvent">
                            <span class="icon icon-delete svg"></span>
                            <translate class="displayname" say="Delete All"/>
                        </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from "jquery";
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import Translate from '@vc/Translate.vue';
    import TagManager from '@js/Manager/TagManager';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
            Translate
        },

        props: {
            newFolder  : {
                type     : Boolean,
                'default': false
            },
            newTag     : {
                type     : Boolean,
                'default': false
            },
            newPassword: {
                type     : Boolean,
                'default': true
            },
            deleteAll  : {
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
                'default': '00000000-0000-0000-0000-000000000000'
            },
            tag        : {
                type     : String,
                'default': null
            }
        },
        data() {
            return {
                showMenu: false
            };
        },

        computed: {
            getHomeIcon() {
                return API.baseUrl + 'core/img/places/home.svg';
            },
            getItems() {
                if(this.items.length === 0) {
                    return [
                        {path: this.$route.path, label: Utility.translate(this.$route.name)}
                    ];
                }

                return this.items;
            }
        },

        methods: {
            toggleMenu() {
                this.showMenu = !this.showMenu;
                this.showMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
            },
            menuEvent($e) {
                if($($e.target).closest('.actions.creatable').length !== 0) return;
                this.showMenu = false;
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
            }
        }
    };
</script>

<style lang="scss">
    #controls {
        top   : auto;
        right : auto;
        left  : auto;

        .actions.creatable {
            margin-left : 10px;
            display     : inline-block;
            position    : relative;
            order       : 2;

            .newPasswordMenu {
                max-height : 0;
                margin     : 0;
                overflow   : hidden;
                transition : max-height 0.25s ease-in-out;
            }

            &.active .newPasswordMenu {
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
            top : 0;
        }

        @keyframes delay-overflow {
            0% { overflow : hidden; }
            99% { overflow : hidden; }
            100% { overflow : visible; }
        }

        @media(min-width : $mobile-width) {
            #app-navigation-toggle {
                display : none;
            }
        }
    }

    .edge {
        .bubble,
        .popovermenu,
        #app-navigation .app-navigation-entry-menu {
            border : none !important;

            &:after {
                border : none !important;
            }
        }
    }
</style>