<template>
    <div class="row password"
         @click="copyPasswordAction($event)"
         @dblclick="copyUsernameAction($event)"
         @dragstart="dragStartAction($event)"
         :data-password-id="password.id">
        <i class="fa fa-star favourite" :class="{ active: password.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon" :style="{'background-image': 'url(' + password.icon + ')'}">&nbsp;</div>
        <span class="title">{{ password.label }}</span>
        <slot name="middle"/>
        <div class="date">{{ password.edited.toLocaleDateString() }}</div>
        <i :class="securityCheck" class="fa fa-shield security"></i>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="passwordActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <translate tag="li" @click="detailsAction($event)" icon="info">Details</translate>
                        <translate tag="li" v-if="isMobile" @click="copyPasswordAction()" icon="clipboard">Copy Password</translate>
                        <translate tag="li" v-if="isMobile" @click="copyUsernameAction()" icon="clipboard">Copy User</translate>
                        <translate tag="li" v-if="password.url" @click="copyUrlAction()" icon="clipboard">Copy Url</translate>
                        <li v-if="password.url">
                            <translate tag="a" :href="password.url" target="_blank" icon="link">Open Url</translate>
                        </li>
                        <translate tag="li" @click="editAction()" icon="pencil" v-if="password.editable">Edit</translate>
                        <translate tag="li" @click="deleteAction()" icon="trash">Delete</translate>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from "jquery";
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate.vue';
    import Utility from "@js/Classes/Utility";
    import Messages from '@js/Classes/Messages';
    import DragManager from '@js/Manager/DragManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        components: {
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                clickTimeout: null,
                showMenu    : false
            }
        },

        computed: {
            securityCheck() {
                switch(this.password.status) {
                    case 0:
                        return 'ok';
                    case 1:
                        return 'warn';
                    case 2:
                        return 'fail';
                }
            },
            isMobile() {
                return window.innerWidth < 361;
            }
        },

        methods: {
            copyPasswordAction($event) {
                if($event && ($event.detail !== 1 || $($event.target).closest('.more').length !== 0)) return;
                Utility.copyToClipboard(this.password.password);

                if(this.clickTimeout) clearTimeout(this.clickTimeout);
                this.clickTimeout =
                    setTimeout(function() { Messages.notification('Password was copied to clipboard') }, 300);
            },
            copyUsernameAction($event) {
                if($event && $($event.target).closest('.more').length !== 0) return;
                if(this.clickTimeout) clearTimeout(this.clickTimeout);

                Utility.copyToClipboard(this.password.username);
                Messages.notification('Username was copied to clipboard');
            },
            copyUrlAction() {
                Utility.copyToClipboard(this.password.url);
                Messages.notification('Url was copied to clipboard')
            },
            favouriteAction($event) {
                $event.stopPropagation();
                this.password.favourite = !this.password.favourite;
                PasswordManager.updatePassword(this.password)
                    .catch(() => { this.password.favourite = !this.password.favourite; });
            },
            toggleMenu($event) {
                this.showMenu = !this.showMenu;
                this.showMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
            },
            menuEvent($e) {
                if($($e.target).closest('[data-password-id=' + this.password.id + '] .more').length !== 0) return;
                this.showMenu = false;
                $(document).off('click', this.menuEvent);
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {type: 'password', element: this.password};
                if(!this.password.hasOwnProperty('revisions')) {
                    API.showPassword(this.password.id, 'model+folder+shares+tags+revisions')
                        .then((p) => {
                            this.$parent.detail = {type: 'password', element: p};
                        })
                }
            },
            editAction() {
                PasswordManager
                    .editPassword(this.password)
                    .then((p) => {this.password = p;});
            },
            deleteAction() {
                PasswordManager.deletePassword(this.password);
            },
            dragStartAction($e) {
                DragManager.start($e, this.password.label, this.password.icon, ['folder'])
                    .then((data) => {
                        PasswordManager.movePassword(this.password, data.folderId)
                            .then((p) => {this.password = p;});
                    });
            }
        }
    }
</script>

<style lang="scss">

    #dragicon {
        padding         : 5px 5px 5px 42px;
        background      : no-repeat 5px;
        background-size : 32px;
        line-height     : 32px;
        display         : inline-block;
    }

    #app-content {
        .item-list {
            .row {
                height        : 51px;
                font-size     : 0;
                border-bottom : 1px solid $color-grey-lighter;
                cursor        : pointer;
                display       : flex;

                .favourite {
                    line-height : 50px;
                    width       : 40px;
                    text-align  : center;
                    color       : $color-grey-light;
                    font-size   : 1rem;
                    flex-shrink : 0;

                    &:hover,
                    &.active {
                        color : $color-yellow;
                    }
                }

                .favicon {
                    display         : inline-block;
                    background      : no-repeat center;
                    background-size : 32px;
                    line-height     : 50px;
                    width           : 50px;
                    font-size       : 1rem;
                    cursor          : pointer;
                    flex-shrink     : 0;
                }

                .title {
                    font-size      : 0.8rem;
                    padding-left   : 8px;
                    cursor         : pointer;
                    line-height    : 50px;
                    min-width      : 0;
                    white-space    : nowrap;
                    overflow       : hidden;
                    text-overflow  : ellipsis;
                    flex-grow      : 1;
                    vertical-align : baseline;
                    display        : flex;
                }

                .more,
                .security {
                    line-height : 50px;
                    width       : 50px;
                    font-size   : 1rem;
                    text-align  : center;
                    flex-shrink : 0;

                    &.security {
                        font-size : 1.25rem;
                    }

                    &.ok {
                        color : $color-green;
                    }
                    &.warn {
                        color : $color-yellow;
                    }
                    &.fail {
                        color : $color-red;
                    }
                }

                .more {
                    position : relative;
                    color    : $color-grey;

                    > i {
                        cursor : pointer;

                        &:active,
                        &:hover {
                            color : $color-black;
                        }
                    }

                    .menu {
                        li {
                            line-height : 40px;
                            font-size   : 0.8rem;
                            padding     : 0 20px 0 15px;
                            white-space : nowrap;
                            color       : $color-grey-darker;
                            font-weight : 300;
                            cursor      : pointer;

                            a {
                                color   : $color-grey-darker;
                                opacity : 1 !important;
                            }

                            i {
                                line-height  : 40px;
                                margin-right : 10px;
                                font-size    : 1rem;
                                width        : 1rem;
                                cursor       : pointer;
                            }

                            &:active,
                            &:hover {
                                background-color : darken($color-white, 3);
                                color            : $color-black;

                                a { color : $color-black; }
                            }
                        }
                    }
                }

                .date {
                    line-height : 50px;
                    width       : 85px;
                    font-size   : 0.8rem;
                    padding     : 0 15px 0 5px;
                    text-align  : right;
                    color       : $color-grey-darker;
                    flex-shrink : 0;
                }

                &:active,
                &:hover {
                    background-color : darken($color-white, 3);

                    .favourite {
                        color : darken($color-grey-light, 3);

                        &:hover,
                        &.active {
                            color : $color-yellow;
                        }
                    }
                }

                @media(max-width : $mobile-width) {
                    .date {
                        display : none;
                    }
                }
            }
        }
    }

</style>