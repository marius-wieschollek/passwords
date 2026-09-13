<template>
    <sharing :password="password" class="password-share-nextcloud"/>
</template>
<script>
    import Sharing from "@vc/Sidebar/PasswordSidebar/Sharing/Sharing";

    export default {
        components: {
            Sharing,
        },

        props: {
            password: {
                type: Object
            }
        }
    }
</script>