<template id="passwords-section-folders">
    <div id="content">
        <passwords-breadcrumb></passwords-breadcrumb>
        <div class="item-list">
            <passwords-line-password :password="password" v-for="password in passwords"></passwords-line-password>
        </div>
    </div>
</template>

<script>
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vc/Line/Password.vue';
    import PasswordDetails from '@vc/Details/Password.vue';

    export default {
        template: '#passwords-section-folders',
        components: {
            'passwords-breadcrumb': Breadcrumb,
            'passwords-details-password': PasswordDetails,
            'passwords-line-password': PasswordLine
        },
        data() {
            return {
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
        }
    };
</script>