<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb></breadcrumb>
            <div class="item-list">
                <password-line :password="password" v-for="password in passwords" :key="password.uuid"></password-line>
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
        components: {
            Breadcrumb,
            'password-details': PasswordDetails,
            'password-line': PasswordLine
        },
        data() {
            return {
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
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
                let array = Utility.sortApiObjectArray(passwords, 'updated', false);
                this.passwords = array.slice(0,15);
            }
        }
    };
</script>