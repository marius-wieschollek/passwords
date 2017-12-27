<template>
    <div id="controls">
        <div class="breadcrumb">
            <div class="crumb svg ui-droppable" data-dir="/">
                <a href="/index.php/apps/passwords">
                    <img class="svg" src="/core/img/places/home.svg" alt="Home">
                </a>
            </div>
            <div class="crumb svg ui-droppable" v-for="item in getItems">
                <router-link :to="item.path" :data-folder-id="item.folderId" :data-drop-type="item.dropType">
                    {{ item.label }}
                </router-link>
            </div>
        </div>
        <div class="actions creatable" v-if="showAddNew" v-bind:class="{active: isOpen}">
            <span class="button new" @click="toggleMenu()">
                <span class="icon icon-add"></span>
            </span>
            <div class="newPasswordMenu popovermenu bubble menu menu-left" @click="toggleMenu()">
                <ul>
                    <li>
                        <span class="menuitem" data-action="folder" v-if="newFolder" @click="createFolder">
                            <span class="icon icon-folder svg"></span>
                            <translate class="displayname">New Folder</translate>
                        </span>
                    </li>
                    <li>
                        <span class="menuitem" data-action="tag" v-if="newTag" @click="createTag">
                            <span class="icon icon-tag svg"></span>
                            <translate class="displayname">New Tag</translate>
                        </span>
                    </li>
                    <li>
                        <span class="menuitem" data-action="file" @click="createPassword($event)">
                            <span class="icon icon-filetype-text svg"></span>
                            <translate class="displayname">New Password</translate>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    import Utility from '@js/Classes/Utility';
    import Translate from '@vc/Translate.vue';
    import TagManager from '@js/Manager/TagManager';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
            Translate
        },

        computed: {
            getItems() {
                if (this.items.length === 0) {
                    return [
                        {path: this.$route.path, label: Utility.translate(this.$route.name)}
                    ]
                }

                return this.items;
            }
        },

        props: {
            newFolder : {
                type     : Boolean,
                'default': false
            },
            newTag    : {
                type     : Boolean,
                'default': false
            },
            showAddNew: {
                type     : Boolean,
                'default': true
            },
            items     : {
                type     : Array,
                'default': () => { return []; }
            },
            folder    : {
                type     : String,
                'default': '00000000-0000-0000-0000-000000000000'
            }
        },
        data() {
            return {
                isOpen: false
            }
        },

        methods: {
            toggleMenu() {
                this.isOpen = !this.isOpen;
            },
            createFolder() {
                FolderManager.createFolder(this.folder);
            },
            createTag() {
                TagManager.createTag();
            },
            createPassword() {
                PasswordManager.createPassword(this.folder);
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

        @keyframes delay-overflow {
            0% { overflow : hidden; }
            99% { overflow : hidden; }
            100% { overflow : visible; }
        }
    }
</style>