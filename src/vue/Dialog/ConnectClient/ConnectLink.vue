<template>
    <div class="connect-client-link">
        <translate tag="div" class="description" :say="description" />
        <qr-code class="qr-code" :text="code" :color="color" :size="300" :bgColor="bgColor" errorLevel="L" v-if="hasCode" />
        <translate tag="a"
                   ref="button"
                   target="_blank"
                   rel="noopener noreferrer"
                   :href="link"
                   class="share-link button primary"
                   say="Connect via link" v-if="hasLink" />
    </div>
</template>

<script>
    import QrCode          from 'vue-qrcode-component';
    import SettingsService from '@js/Services/SettingsService';
    import Translate       from '@vc/Translate';
    import API             from '@js/Helper/api';
    import ColorConvert    from 'color-convert';
    import DeltaE          from 'delta-e';

    export default {
        components: {Translate, QrCode},

        props: {
            hasLink : Boolean,
            hasCode : Boolean,
            protocol: String
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
            },
            description() {
                if(this.hasCode && this.hasLink) {
                    return 'Click the button to connect an app installed on this device or scan the QR code with the app if it\'s installed on another device.';
                }

                if(this.hasLink) return 'Click the button when you are ready to start the connection.';
                if(this.hasCode) return 'Just scan the QR code with the app to connect it.';
            }
        },

        methods: {
            async requestConnection() {
                if(!this.active) return;
                let data = await API.passLinkConnectRequest();
                if(data.links.hasOwnProperty(this.protocol)) {
                    this.link = data.links[this.protocol];
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
        margin     : 1rem 0;
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