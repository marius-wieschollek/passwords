<template>
    <div id="controls">
        <div class="breadcrumb">
            <div class="crumb svg ui-droppable" data-dir="/">
                <a href="/index.php/apps/passwords">
                    <img class="svg" src="/core/img/places/home.svg" alt="Home">
                </a>
            </div>
            <div class="crumb svg ui-droppable" v-for="item in getItems">
                <router-link :to="item.path">{{ item.label }}</router-link>
            </div>
        </div>
        <div class="actions creatable" v-if="showAddNew" v-bind:class="{active: isOpen}">
            <span class="button new" @click="toggleMenu()">
                <span class="icon icon-add"></span>
            </span>
            <div class="newPasswordMenu popovermenu bubble menu open menu-left" @click="toggleMenu()">
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
    import Vue from 'vue';
    import $ from "jquery";
    import API from "@js/Helper/api";
    import Utility from '@js/Classes/Utility';
    import Translate from '@vc/Translate.vue';
    import PwMessages from '@js/Classes/Messages';
    import CreateDialog from '@vue/Dialog/CreatePassword.vue';

    export default {
        components: {
            Translate
        },

        computed: {
            getItems() {

                if(this.items.length === 0) {
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
            items: {
                type     : Array,
                'default': () => { return []; }
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
                PwMessages
                    .prompt('Enter folder name', 'Create folder')
                    .then((title) => {
                        let folder = {
                            label: title
                        };

                        API.createFolder(folder)
                            .then(() => {

                                PwMessages.notification('Folder created');
                            })
                    });
            },
            createTag() {
                PwMessages.prompt('test');
            },
            createPassword() {
                let PasswordCreateDialog = Vue.extend(CreateDialog);
                new PasswordCreateDialog().$mount('#app-popup div');
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