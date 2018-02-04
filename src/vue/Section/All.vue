<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb/>
            <div class="item-list">
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';

    export default {
        data() {
            return {
                loading  : true,
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
        },

        components: {
            Breadcrumb,
            PasswordDetails,
            PasswordLine
        },

        created() {
            this.refreshView();
            Events.on('password.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('password.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView: function() {
                API.listPasswords().then(this.updateContentList);
            },

            updateContentList: function(passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, 'label', true);
            }
        }
    }
</script>

<style lang="scss">

    #app-content {
        position   : relative;
        height     : 100%;
        overflow-y : auto;
        transition : margin-right 300ms, transform 300ms;

        .app-content-right {
            background-color : white;
            z-index          : 50;
            border-left      : 1px solid $color-grey-light;
            transition       : right 300ms;
            right            : -27%;
        }

        &.show-details {
            margin-right : 27%;

            .app-content-right {
                display    : block;
                position   : fixed;
                top        : 45px;
                right      : 0;
                left       : auto;
                bottom     : 0;
                width      : 27%;
                min-width  : 360px;
                overflow-y : auto;
            }
        }

        > #app-navigation-toggle {
            display : none !important;
        }

        @media(max-width : $tablet-width) {
            transform : translate3d(0, 0, 0);

            .app-content-right {
                border-left : none;
                transition  : width 300ms;
            }

            &.show-details {
                margin-right : 0;

                .app-content-left {
                    display : none;
                }
                .app-content-right {
                    width     : 100%;
                    min-width : auto;
                    top       : 0;
                }
            }

            &.mobile-open {
                transform : translate3d(250px, 0px, 0px);
            }
        }

        @media(max-width : $desktop-width) {
            .app-content-right {
                right : -360px;
            }

            &.show-details {
                margin-right : 360px;

                .app-content-right {
                    width     : 360px;
                    min-width : 360px;
                }
            }
        }
    }

    [data-server-version="12"] #app-content {
        .item-list {
            padding-top : 44px;
        }
    }
</style>