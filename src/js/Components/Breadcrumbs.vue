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
        <div class="actions creatable" v-if="showAddNew">
            <span class="button new" @click="clickAddButton($event)">
                <span class="icon icon-add"></span>
            </span>
            <div class="newPasswordMenu popovermenu bubble menu open menu-left" @click="clickAddButton($event)">
                <ul>
                    <li>
                        <span class="menuitem" data-action="folder" v-if="newFolder">
                            <span class="icon icon-folder svg"></span>
                            <translate class="displayname">New Folder</translate>
                        </span>
                    </li>
                    <li>
                        <span class="menuitem" data-action="tag" v-if="newTag">
                            <span class="icon icon-tag svg"></span>
                            <translate class="displayname">New Tag</translate>
                        </span>
                    </li>
                    <li>
                        <span class="menuitem" data-action="file" @click="clickCreatePassword($event)">
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
    import CreateDialog from '@vc/Dialog/CreatePassword.vue';
    import Translate from '@vc/Translate.vue';
    import Utility from '@js/Classes/Utility';

    export default {
        data() {
            return {
                items: []
            }
        },
        components: {
            Translate
        },

        computed: {
            getItems() {
                let items = this.items.clone();
				items.unshift({path: this.$route.path, label: Utility.translate(this.$route.name)});


                return items;
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
            }
        },

        methods: {
            clickAddButton($event) {
                $($event.target).parents('.creatable').toggleClass('active');
            },
            clickCreatePassword($event) {
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