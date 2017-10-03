<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb></breadcrumb>
            <div class="item-list">
                <password-line
                        :password="password"
                        v-for="password in passwords"
                        v-if="!password.trashed"
                        :key="password.uuid">
                </password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type == 'password'" :password="detail.element"></password-details>
        </div>
    </div>
</template>

<script>
    import PwEvents from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vc/Line/Password.vue';
    import PasswordDetails from '@vc/Details/Password.vue';
    import API from '@js/Helper/api';

    export default {
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
            Breadcrumb,
            'password-details': PasswordDetails,
            'password-line'   : PasswordLine
        },

        created() {
            this.refreshView();
            PwEvents.on('data.changed', this.refreshView);
        },

        beforeDestroy() {
            PwEvents.off('data.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView: function () {
                API.listPasswords().then(this.updateContentList);
            },

            updateContentList: function (passwords) {
                this.passwords = Utility.sortApiObjectArray(passwords, 'title', true);
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
                display  : block;
                position : fixed;
                top      : 45px;
                right    : 0;
                left     : auto;
                bottom   : 0;
                width    : 27%;
            }
        }

        .item-list {
            padding-top : 44px;
        }
    }

</style>