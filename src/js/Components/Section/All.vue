<template type="text/x-template" id="passwords-section-all">
    <div id="content" v-bind:class="{ 'show-details': showDetails }">
        <div class="content-left">
            <passwords-breadcrumb></passwords-breadcrumb>
            <div class="item-list">
                <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
            </div>
        </div>
        <div class="content-right">
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

    #content {
        display               : grid;
        grid-template-columns : 3fr 0;
        grid-template-areas   : "left right";
        grid-template-rows    : auto;
        justify-items         : stretch;
        align-items           : stretch;
        transition            : grid-template-columns 0.5s ease-in-out;
        position              : absolute;
        top                   : 0;
        bottom                : 0;

        .content-left {
            grid-area  : left;
            margin-top : 44px;
        }
        .content-right {
            display     : none;
            grid-area   : right;
            z-index     : 50;
            border-left : 1px solid $color-grey-light
        }

        &.show-details {
            grid-template-columns : 2.05fr 0.95fr;
            .content-right {
                display : block;
            }
        }

        .item-list {
            margin-top : 44px;
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

        .item-details {
            .image-container {
                height   : 290px;
                overflow : hidden;

                a {
                    display   : block;
                    font-size : 0;

                    img {
                        width      : 100%;
                        margin-top : 0;
                        transition : none;

                        &.s1 { transition : margin-top 1s ease-in-out; }
                        &.s5 { transition : margin-top 5s ease-in-out; }
                        &.s10 { transition : margin-top 10s ease-in-out; }
                        &.s15 { transition : margin-top 15s ease-in-out; }
                        &.s20 { transition : margin-top 20s ease-in-out; }
                    }
                }
            }
        }
    }

</style>