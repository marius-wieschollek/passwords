<template>
    <tabs :tabs="getSharingTabs">
        <sharing slot="nextcloud" :password="password" class="password-share-nextcloud"/>
        <qr-code slot="qrcode" :password="password"/>
    </tabs>
</template>
<script>
    import Tabs from '@vc/Sidebar/PasswordSidebar/Tabs';
    import Sharing from "@vc/Sidebar/PasswordSidebar/Sharing/Sharing.vue";
    import QrCode from "@vc/Sidebar/PasswordSidebar/Sharing/QrCode.vue";
    import NcAppSidebarTab from '@nc/NcAppSidebarTab';
    import SettingsService from "@js/Services/SettingsService";

    export default {
        components: {
            Sharing,
            QrCode,
            Tabs,
            NcAppSidebarTab,
        },

        props: {
            password: {
                type: Object
            }
        },

        computed: {
            getSharingTabs() {
                if(SettingsService.get('server.sharing.enabled')) {
                    return {nextcloud: 'Share', qrcode: 'QR Code'};
                }
                return {qrcode: 'QR Code'};
            }
        },
    }
</script>