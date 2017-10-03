<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false"></breadcrumb>
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
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vc/Line/Password.vue';
    import PasswordDetails from '@vc/Details/Password.vue';

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
        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        }
    };
</script>