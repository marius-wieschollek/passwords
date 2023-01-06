<template>
    <sharing :password="password" class="password-share-nextcloud"/>
</template>
<script>
    import Sharing from "@vc/Sidebar/PasswordSidebar/Sharing/Sharing";
    import NcAppSidebarTab from '@nc/NcAppSidebarTab';

    export default {
        components: {
            Sharing,
            NcAppSidebarTab,
        },

        props: {
            password: {
                type: Object
            }
        }
    }
</script>