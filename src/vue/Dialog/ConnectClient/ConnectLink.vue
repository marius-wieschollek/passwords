<template>
    <div class="connect-client-link">
        <translate tag="div" class="description" say="To connect a new device or app, simply scan the QR code or click the link."/>
        <qr-code class="qr-code" :text="code" :color="color" :size="300" :bgColor="bgColor" errorLevel="L"/>
        <translate tag="a"
                   target="_blank"
                   rel="noopener noreferrer"
                   :href="link"
                   class="share-link button primary"
                   say="Connect via link"/>
    </div>
</template>

<script>
    import QrCode from 'vue-qrcode-component';
    import SettingsService from '@js/Services/SettingsService';
    import Translate from '@vc/Translate';
    import API from '@js/Helper/api';
    import ColorConvert from 'color-convert';
    import DeltaE from 'delta-e';

    export default {
        components: {Translate, QrCode},

        props: {
            useAlternativeLink: Boolean
        },

        data() {
            return {
                link  : '',
                code  : '',
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

        computed: {
            color() {
                let themeColor = SettingsService.get('server.theme.color.primary'),
                    labColor   = ColorConvert.hex.lab(themeColor.substr(1)),
                    labBgColor = ColorConvert.hex.lab(this.bgColor.substr(1)),
                    labWhite   = {L: 100, A: 0, B: 0};

                labColor = {L: labColor[0], A: labColor[1], B: labColor[2]};
                labBgColor = {L: labBgColor[0], A: labBgColor[1], B: labBgColor[2]};

                if(DeltaE.getDeltaE00(labBgColor, labColor) > 30) {
                    return themeColor;
                }

                if(DeltaE.getDeltaE00(labBgColor, labWhite) > 30) {
                    return '#fff';
                }

                return '#000';
            },
            bgColor() {
                return '#fff0';
            }
        },

        methods: {
            async requestConnection() {
                if(!this.active) return;
                let data = await API.passLinkConnectRequest();
                if(this.useAlternativeLink) {
                    this.link = data.alternativeLink;
                } else {
                    this.link = data.link;
                }
                this.code = data.link;
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
        margin     : 1rem;
        text-align : center;

    }

    .qr-code {
        margin : 2rem auto;
        width  : 300px;
    }

    .share-link {
        display    : block;
        margin     : 1rem;
        text-align : center;
    }
}
</style>