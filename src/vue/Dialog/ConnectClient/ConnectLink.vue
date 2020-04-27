<template>
    <div class="connect-client-link">
        <translate tag="div"
                   class="description"
                   say="To connect a new device or app, simply scan the QR code or click the link."/>
        <qr-code class="qr-code" :text="link" :color="color" :size="256" bgColor="#fff0" errorLevel="H"/>
        <translate tag="a" target="_blank" :href="link" class="share-link button primary" say="Connect via link"/>
    </div>
</template>

<script>
    import QrCode from 'vue-qrcode-component';
    import SettingsService from '@js/Services/SettingsService';
    import Translate from '@vc/Translate';
    import API from '@js/Helper/api';

    export default {
        components: {Translate, QrCode},

        data() {
            return {
                color : SettingsService.get('server.theme.color.primary'),
                link  : '',
                id    : '',
                active: false
            };
        },

        async mounted() {
            this.active = true;
            do {
                await this.requestConnection();
            } while(this.active && !await this.awaitResponse());
        },

        beforeDestroy() {
            this.active = false;
        },

        methods: {
            async requestConnection() {
                if(!this.active) return;
                let data = await API.passLinkConnectRequest();
                this.link = data.link;
                this.id = data.id;
            },
            async awaitResponse() {
                try {
                    let data = await API.passLinkConnectAwait();
                    this.$emit('connect', data);
                    return true;
                } catch(e) {
                    return false;
                }
            }
        }
    };
</script>

<style lang="scss">
    #passlink-connect .connect-client-link {
        .description {
            margin    : 1rem;
            text-align: center;

        }

        .qr-code {
            margin : 2rem auto;
            width  : 256px;
        }

        .share-link {
            display    : block;
            margin     : 1rem;
            text-align : center;
        }
    }
</style>