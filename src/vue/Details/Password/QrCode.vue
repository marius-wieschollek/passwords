<template>
    <div class="password-share-qrcode">
        <select id="password-details-qrcode" v-model="property">
            <translate tag="option" value="username" v-if="password.username" say="Username"/>
            <translate tag="option" value="password" say="Password"/>
            <translate tag="option" value="url" v-if="password.url" say="Website"/>
            <translate tag="option" value="hash" say="SHA-1"/>
        </select>
        <qr-code :text="text" :color="color" :size="256" bgColor="#fff0" errorLevel="H"/>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import QrCode from 'vue-qrcode-component';
    import SettingsService from '@js/Services/SettingsService';

    export default {
        components: {
            QrCode,
            Translate
        },

        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                color   : SettingsService.get('server.theme.color.primary'),
                text    : this.password.password,
                property: 'password'
            };
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

        img,
        canvas {
            display : block;
            margin  : 0 auto;
        }
    }
</style>