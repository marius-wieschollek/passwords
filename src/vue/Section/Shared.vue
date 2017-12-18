<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false"></breadcrumb>
            <div class="item-list">
                <password-line :password="password" v-for="password in passwords" :key="password.id"></password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type == 'password'" :password="detail.element"></password-details>
        </div>
    </div>
</template>

<script>
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';

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
        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        }
    };
</script>