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
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';
    import API from '@js/Helper/api';

    export default {
        components: {
            Breadcrumb,
            PasswordDetails,
            PasswordLine
        },
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
                let array = Utility.sortApiObjectArray(passwords, 'updated', false);
                this.loading = false;
                this.passwords = array.slice(0, 15);
            }
        }
    };
</script>