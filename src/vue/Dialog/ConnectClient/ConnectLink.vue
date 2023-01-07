<template>
    <div class="connect-client-link">
        <translate tag="div" class="description" :say="description"/>
        <qr-code class="qr-code" :text="code" :size="300" v-if="hasCode"/>
        <translate tag="a"
                   ref="button"
                   target="_blank"
                   rel="noopener noreferrer"
                   :href="link"
                   class="connect-link button primary"
                   say="Connect via link" v-if="hasLink"/>
    </div>
</template>

<script>
    import QrCode from '@vc/QrCode';
    import Translate from '@vc/Translate';
    import API from '@js/Helper/api';

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
    }

    .connect-link {
        display    : block;
        margin     : 1rem;
        text-align : center;
    }
}
</style>