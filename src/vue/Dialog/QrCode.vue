<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-modal :container="container" size="small" v-on:close="close">
        <div class="password-share-qrcode">
            <select id="password-details-qrcode" v-model="property">
                <translate tag="option" value="username" v-if="password.username" say="Username"/>
                <translate tag="option" value="password" say="Password"/>
                <translate tag="option" value="url" v-if="password.url" say="Website"/>
                <translate tag="option" value="hash" say="SHA-1"/>
            </select>
            <div class="password-share-qrcode-box" :class="{'has-warning':showWarning}">
                <qr-code :text="text"/>
                <div class="password-share-qrcode-warning" v-if="showWarning" @click="hideWarning">
                    <eye-off-icon :size="64"/>
                    <translate say="Make sure this QR code is only visible to people you trust."/>
                    <translate say="Click to show the QR code."/>

                    <translate class="disable-warning" say="Don't warn me again" @click.stop="disableWarning"/>
                </div>
            </div>
        </div>
    </nc-modal>
</template>

<script>
    import Translate from '@vc/Translate';
    import QrCode from '@vc/QrCode';
    import Icon from "@vc/Icon";
    import Localisation from "@js/Classes/Localisation";
    import NcModal from '@nc/NcModal.js';
    import EyeOffIcon from "@icon/EyeOff";
    import UtilityService from "@js/Services/UtilityService";
    import SettingsService from '@js/Services/SettingsService';

    export default {
        components: {
            EyeOffIcon,
            Icon,
            QrCode,
            Translate,
            NcModal
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            let warning = SettingsService.get('client.sharing.qrcode.warning', true);

            return {
                text     : warning ? Localisation.translate('What did you expect?'):this.password.password,
                property : 'password',
                container: UtilityService.popupContainer(true),
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
                SettingsService.set('client.sharing.qrcode.warning', false);
                this.hideWarning();
            },
            close() {
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);
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
    padding : 1rem 1rem 2rem;

    select {
        width         : calc(100% - 2rem);
        margin-bottom : 15px;
        cursor        : pointer;
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

            .material-design-icon {
                margin  : 0 auto 0;
                display : block;

                svg {
                    width  : 96px;
                    height : 96px;
                }
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

        .passwords-qr-code {
            display    : block;
            margin     : 0 auto;
            transition : opacity .25s ease-in-out, filter .25s ease-in-out;
        }

        &.has-warning {
            .passwords-qr-code {
                opacity : .25;
                filter  : blur(5px);
            }
        }
    }
}
</style>