<template type="text/x-template" id="passwords-section-all">
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <passwords-breadcrumb></passwords-breadcrumb>
            <div class="item-list">
                <passwords-line-password :password="password" v-for="password in passwords" v-if="!password.trashed"></passwords-line-password>
            </div>
        </div>
        <div class="app-content-right">
            <passwords-details-password v-if="detail.type == 'password'" :password="detail.element"></passwords-details-password>
        </div>
    </div>
</template>

<script>
    import PwEvents from "@js/Classes/Events";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vc/Line/Password.vue';
    import PasswordDetails from '@vc/Details/Password.vue';
    import API from '@js/Helper/api';

    export default {
        template: '#passwords-section-all',
        data() {
            return {
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
        },

        components: {
            'passwords-breadcrumb'      : Breadcrumb,
            'passwords-details-password': PasswordDetails,
            'passwords-line-password'   : PasswordLine
        },

        created() {
            this.openAllPasswordsPage();
            PwEvents.on('data.changed', this.openAllPasswordsPage);
        },

        beforeDestroy() {
            PwEvents.off('data.changed', this.openAllPasswordsPage)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            openAllPasswordsPage: function () {
                API.listPasswords().then(this.updateContentList);
            },

            updateContentList: function (passwords) {
                this.passwords = passwords;
            }
        }
    }
</script>

<style lang="scss">

    #app-content {
        position   : relative;
        height     : 100%;
        overflow-y : auto;
        transition : margin-right 300ms;

        .app-content-right {
            z-index     : 50;
            border-left : 1px solid $color-grey-light;
            transition  : right 300ms;
            right       : -27%;
        }

        &.show-details {
            margin-right : 27%;

            .app-content-right {
                display   : block;
                position  : fixed;
                top       : 45px;
                right     : 0;
                left      : auto;
                bottom    : 0;
                width     : 27%;
            }
        }

        .item-list {
            padding-top : 44px;

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
                }

                .more,
                .security {
                    float       : right;
                    line-height : 50px;
                    width       : 50px;
                    font-size   : 1rem;
                    text-align  : center;

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
                            color       : $color-grey-dark;
                            cursor      : pointer;

                            a { color : $color-grey-dark; }

                            i {
                                margin-right : 10px;
                                font-size    : 1rem;
                                width        : 1rem;
                                text-align   : center;
                                position     : relative;
                                bottom       : -0.1rem;
                                cursor       : pointer;
                            }

                            span {
                                font-weight : 300;
                                cursor      : pointer;
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
                    color       : $color-grey-dark;
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