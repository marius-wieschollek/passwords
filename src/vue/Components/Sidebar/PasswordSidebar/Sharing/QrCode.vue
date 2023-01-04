<template>
    <div class="password-share-qrcode">
        <select id="password-details-qrcode" v-model="property">
            <translate tag="option" value="username" v-if="password.username" say="Username"/>
            <translate tag="option" value="password" say="Password"/>
            <translate tag="option" value="url" v-if="password.url" say="Website"/>
            <translate tag="option" value="hash" say="SHA-1"/>
        </select>
        <div class="password-share-qrcode-box" :class="{'has-warning':showWarning}">
            <qr-code :text="text" :color="color" :size="256" bgColor="#fff0" errorLevel="Q"/>
            <div class="password-share-qrcode-warning" v-if="showWarning" @click="hideWarning">
                <icon icon="eye-slash"/>
                <translate say="Make sure this QR code is only visible to people you trust."/>
                <translate say="Click to show the QR code."/>

                <translate class="disable-warning" say="Don't warn me again" @click.stop="disableWarning"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import QrCode from 'vue-qrcode-component';
    import SettingsService from '@js/Services/SettingsService';
    import Icon from "@vc/Icon";
    import Localisation from "@js/Classes/Localisation";

    export default {
        components: {
            Icon,
            QrCode,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            let warning = SettingsService.get('local.sharing.qrcode.warning', true);

            return {
                color   : SettingsService.get('server.theme.color.primary'),
                text    : warning ? Localisation.translate('What did you expect?'):this.password.password,
                property: 'password',
                warning
            };
        },

        computed: {
            showWarning() {
                return this.warning;
            }
        },

        methods: {
            hideWarning() {
                this.text = this.password[this.property];
                this.$nextTick(() => {
                    this.warning = false;
                });
            },
            disableWarning() {
                SettingsService.set('local.sharing.qrcode.warning', false);
                this.hideWarning();
            }
        },

        watch: {
            password(value) {
                this.text = value[this.property];
            },
            property(value) {
                this.text = this.password[value];
            }
        }
    };
</script>

<style lang="scss">
.password-share-qrcode {
    select {
        width         : 100%;
        margin-bottom : 15px;
    }

    .password-share-qrcode-box {
        position : relative;

        .password-share-qrcode-warning {
            position        : absolute;
            top             : 0;
            left            : 0;
            right           : 0;
            bottom          : 0;
            cursor          : pointer;
            display         : flex;
            flex-direction  : column;
            justify-content : center;

            i {
                font-size : 5rem;
                margin    : 0 auto 1.25rem;
                display   : block;
            }

            span {
                max-width  : 256px;
                padding    : 0 1rem;
                margin     : 0 auto;
                text-align : center;
                display    : block;
            }

            .disable-warning {
                margin-top : 1.5rem;
                transition : color .15s ease-in-out;
                cursor     : pointer;

                &:hover {
                    color : var(--color-primary);
                }
            }
        }

        img,
        canvas {
            display    : block;
            margin     : 0 auto;
            transition : opacity .25s ease-in-out, filter .25s ease-in-out;
        }

        &.has-warning {
            img,
            canvas {
                opacity : .25;
                filter  : blur(5px);
            }
        }
    }
}
</style>