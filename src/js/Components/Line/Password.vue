<template>
    <div class="row password"
         @click="singleClickAction($event)"
         @dblclick="doubleClickAction()"
         v-if="password"
         :data-password-id="password.id">
        <i class="fa fa-star favourite" v-bind:class="{ active: password.favourite }" @click="favouriteAction($event)"></i>
        <div v-bind:style="faviconStyle" class="favicon">&nbsp;</div>
        <span class="title">{{ password.title }}</span>
        <div class="date">{{ date }}</div>
        <i v-bind:class="securityCheck" class="fa fa-shield security"></i>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="passwordActionsMenu popovermenu bubble menu">
                <slot name="menu">
                <ul>
                    <slot name="option-top"></slot>
                    <translate tag="li" @click="detailsAction($event);" icon="info">Details</translate>
                    <translate tag="li" v-if="password.url" @click="copyUrlAction()" icon="clipboard">Copy Url</translate>
                    <li v-if="password.url">
                        <translate tag="a" :href="password.url" target="_blank" icon="link">Open Url</translate>
                    </li>
                    <translate tag="li" icon="pencil">Edit</translate>
                    <translate tag="li" @click="deleteAction()" icon="trash">Delete</translate>
                    <slot name="option-bottom"></slot>
                </ul>
                </slot>
            </div>
        </div>
        <slot name="buttons"></slot>
    </div>
</template>

<script>
    import PwMessages from '@js/Classes/Messages';
    import Utility from "@js/Classes/Utility";
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate.vue';

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
                clickTimeout: null
            }
        },

        computed: {
            faviconStyle() {
                return {
                    backgroundImage: 'url(' + this.password.icon + ')'
                }
            },
            date() {
                return new Date(this.password.updated * 1e3).toLocaleDateString();
            },
            securityCheck() {
                switch (this.password.status) {
                    case 0:
                        return 'ok';
                    case 1:
                        return 'warn';
                    case 2:
                        return 'fail';
                }
            }
        },

        methods: {
            singleClickAction($event) {
                if ($event.detail !== 1) return;
                Utility.copyToClipboard(this.password.password);

                if (this.clickTimeout) clearTimeout(this.clickTimeout);
                this.clickTimeout =
                    setTimeout(function () { PwMessages.notification('Password was copied to clipboard') }, 300);
            },
            doubleClickAction() {
                if (this.clickTimeout) clearTimeout(this.clickTimeout);

                Utility.copyToClipboard(this.password.login);
                PwMessages.notification('Username was copied to clipboard');
            },
            favouriteAction($event) {
                $event.stopPropagation();
                this.password.favourite = !this.password.favourite;
                API.updatePassword(this.password);
            },
            toggleMenu($event) {
                $event.stopPropagation();
                $($event.target).parents('.row.password').find('.passwordActionsMenu').toggleClass('open');
            },
            copyUrlAction() {
                Utility.copyToClipboard(this.password.url);
                PwMessages.notification('Url was copied to clipboard')
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'password',
                    element: this.password
                }
            },
            deleteAction(skipConfirm = false) {
                if(skipConfirm || !this.password.trashed) {
                    API.deletePassword(this.password.id)
                        .then(() => {
                            this.password = undefined;
                            PwMessages.notification('Password was deleted');
                        }).catch(() => {
                        PwMessages.notification('Deleting password failed');
                    });
                } else {
                    PwMessages.confirm('Do you want to delete the password', 'Delete password')
                        .then(() => { this.deleteAction(true); })
                }
            }
        }
    }
</script>

<style lang="scss">

    #app-content {
        .item-list {
            .row {
                height        : 51px;
                font-size     : 0;
                border-bottom : 1px solid $color-grey-lighter;
                cursor        : pointer;

                .favourite {
                    line-height : 50px;
                    width       : 40px;
                    text-align  : center;
                    color       : $color-grey-light;
                    font-size   : 1rem;

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
                }

                .title {
                    font-size    : 0.8rem;
                    padding-left : 8px;
                    cursor       : pointer;
                    line-height     : 50px;
                    display: inline-flex;
                }

                .more,
                .security {
                    float       : right;
                    line-height : 50px;
                    width       : 50px;
                    font-size   : 1rem;
                    text-align  : center;

                    &.security {
                        font-size   : 1.25rem;
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
                                color : $color-grey-darker;
                                opacity: 1 !important;
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
                    float       : right;
                    line-height : 50px;
                    width       : 85px;
                    font-size   : 0.8rem;
                    padding     : 0 15px 0 5px;
                    text-align  : right;
                    color       : $color-grey-darker;
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
            }
        }
    }

</style>