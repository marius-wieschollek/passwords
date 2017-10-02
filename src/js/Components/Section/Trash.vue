<template id="passwords-section-trash">
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <passwords-breadcrumb></passwords-breadcrumb>
            <div class="item-list">
                <passwords-line-password :password="password" v-for="password in passwords" v-if="password.trashed"></passwords-line-password>
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