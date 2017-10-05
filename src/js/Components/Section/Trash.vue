<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false"></breadcrumb>
            <div class="item-list">
                <password-line :password="password" v-for="password in passwords" v-if="password.trashed" :key="password.uuid">
                    <translate tag="li" icon="undo" slot="option-top" @click="untrashAction(password)">Untrash</translate>
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
    import Translate from '@vc/Translate.vue';
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
            Translate,
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
                API.findPasswords({trashed:true}).then(this.updateContentList);
            },

            updateContentList: function (passwords) {
                this.passwords = Utility.sortApiObjectArray(passwords, 'title');
            },

            untrashAction(password) {
                password.trashed = false;
                API.updatePassword(password);
            }
        }
    }
</script>